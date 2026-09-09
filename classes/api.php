<?php

// This file is part of Course Agent - AI Course Creator Plugin for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * API service for generating and publishing AI-created Moodle courses.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.LineLength.TooLong
// phpcs:disable moodle.Commenting.InlineComment.NotCapital
// phpcs:disable moodle.Commenting.InlineComment.InvalidEndChar
// phpcs:disable moodle.Strings.ForbiddenStrings.Found
// phpcs:disable PSR1.Files.SideEffects -- Moodle requires bootstrap (MOODLE_INTERNAL, require_once)
namespace local_courseagent;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/externallib.php');


/**
 * Course Agent API class - handles course generation and publishing.
 *
 * Class name is lowercase to match the file name (api.php). Moodle's autoloader
 * resolves `local_courseagent\Api` to `classes/Api.php` literally, so a PascalCase
 * class name in a lowercase file fails on case-sensitive filesystems (Linux).
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MissingNamespace
class api
{
    /** @var string[] Non-fatal H5P generation warnings collected during publishCourse(). */
    private $h5pwarnings = [];

    /**
     * Normalize the raw activity-count params (sent by the course form) into a tidy
     * per-activity struct. Each activity has an independently-toggleable "per section"
     * range and "total across course" cap.
     *
     * @param array $raw Raw params keyed like 'quiz_min_per_section', 'quiz_total_enabled', ...
     * @return array{quiz: array, assignment: array, h5p: array}
     */
    public static function resolveCounts(array $raw)
    {
        $int = function ($k, $d) use ($raw) {
            return isset($raw[$k]) ? max(0, (int) $raw[$k]) : $d;
        };
        $bool = function ($k) use ($raw) {
            return !empty($raw[$k]);
        };
        $one = function ($p) use ($int, $bool) {
            $permin = max(1, $int($p . '_min_per_section', 1));
            $permax = max($permin, $int($p . '_max_per_section', $permin));
            $totmin = max(1, $int($p . '_min_total', 1));
            $totmax = max($totmin, $int($p . '_max_total', $totmin));
            return [
                'per_section_enabled' => $bool($p . '_per_section_enabled'),
                'per_min'             => $permin,
                'per_max'             => $permax,
                'total_enabled'       => $bool($p . '_total_enabled'),
                'total_min'           => $totmin,
                'total_max'           => $totmax,
            ];
        };
        return [
            'quiz'       => $one('quiz'),
            'assignment' => $one('assignment'),
            'h5p'        => $one('h5p'),
        ];
    }

    /**
     * Build a one-line natural-language count instruction for a single activity, used in prompts.
     * Returns '' when the activity is disabled.
     *
     * @param string $label   Display label, e.g. 'Quizzes'
     * @param array  $cfg     One activity's resolved count struct
     * @param bool   $enabled Whether the parent activity toggle is on
     * @return string
     */
    private function countInstruction($label, $cfg, $enabled)
    {
        if (!$enabled) {
            return '';
        }
        $persec = !empty($cfg['per_section_enabled']);
        $total  = !empty($cfg['total_enabled']);
        if (!$persec && !$total) {
            return "- {$label}: exactly 1 per section.\n";
        }
        $parts = [];
        if ($persec) {
            $parts[] = ($cfg['per_min'] === $cfg['per_max'])
                ? "exactly {$cfg['per_min']} per section"
                : "between {$cfg['per_min']} and {$cfg['per_max']} per section";
        }
        if ($total) {
            $parts[] = ($cfg['total_min'] === $cfg['total_max'])
                ? "exactly {$cfg['total_min']} in total across the whole course"
                : "between {$cfg['total_min']} and {$cfg['total_max']} in total across the whole course";
        }
        $extra = ($persec && $total)
            ? ' If these two limits conflict, the course total is the hard cap.'
            : '';
        return "- {$label}: " . implode(', and ', $parts) . ".{$extra}\n";
    }

    /**
     * The hard per-section ceiling for an activity (used by the publish layer to clamp
     * however many items the AI returned). Falls back to a generous default when the
     * activity's "per section" sub-option is off.
     *
     * @param array $cfg One activity's resolved count struct
     * @return int
     */
    private function perSectionCap($cfg)
    {
        if (!empty($cfg['per_section_enabled'])) {
            return max(1, (int) $cfg['per_max']);
        }
        // Per-section off but total on → let the total cap govern; allow up to the total max per section.
        if (!empty($cfg['total_enabled'])) {
            return max(1, (int) $cfg['total_max']);
        }
        return 1;
    }

    /**
     * Coerce a counts struct (which may have survived a JSON/session round-trip as a stdClass)
     * back into a plain array-of-arrays, filling any gaps with defaults.
     *
     * @param array|object|null $counts
     * @return array{quiz: array, assignment: array, h5p: array}
     */
    private function countsToArray($counts)
    {
        $defaults = self::resolveCounts([]);
        $out = [];
        foreach (['quiz', 'assignment', 'h5p'] as $a) {
            $cfg = is_object($counts) ? ($counts->$a ?? null)
                 : (is_array($counts) ? ($counts[$a] ?? null) : null);
            $out[$a] = $cfg ? array_merge($defaults[$a], (array) $cfg) : $defaults[$a];
        }
        return $out;
    }

    /**
     * Normalize a section so quizzes / assignments / H5P are always arrays, coercing the
     * older singular shapes (quiz, assignment, h5p_type) the AI or saved sessions may use.
     *
     * @param \stdClass $section
     * @return \stdClass
     */
    private function normalizeSection($section)
    {
        // Quizzes → array of quiz objects that actually have questions.
        if (!isset($section->quizzes) || !is_array($section->quizzes)) {
            $section->quizzes = (!empty($section->quiz) && !empty($section->quiz->questions))
                ? [$section->quiz] : [];
        }
        $section->quizzes = array_values(array_filter($section->quizzes, function ($q) {
            return !empty($q->questions);
        }));

        // Assignments → array of assignment objects.
        if (!isset($section->assignments) || !is_array($section->assignments)) {
            $section->assignments = !empty($section->assignment) ? [$section->assignment] : [];
        }
        $section->assignments = array_values(array_filter($section->assignments, function ($a) {
            return !empty($a);
        }));

        // H5P → array of type strings (the plan stamps this; fall back to the single legacy type).
        if (!isset($section->h5p) || !is_array($section->h5p)) {
            $section->h5p = !empty($section->h5p_type) ? [$section->h5p_type] : [];
        }
        $section->h5p = array_values(array_filter($section->h5p, function ($t) {
            return is_string($t) && $t !== '';
        }));

        return $section;
    }

    /**
     * Generate course outline using AI.
     *
     * @param string $topic Course topic
     * @param string $level Course level (beginner, intermediate, advanced)
     * @param int $numsections Number of sections
     * @param bool $includequiz Include quizzes
     * @param bool $includeassignment Include assignments
     * @param int|null $providerid Provider ID or null for default
     * @param string|null $model AI model name or null for first available
     * @return \stdClass Course data
     */
    public function generateCourseOutline(
        $topic,
        $level,
        $numsections,
        $includequiz = true,
        $includeassignment = false,
        $providerid = null,
        $model = null,
        $uploadedcontent = null,
        $customtitle = null,
        $useemojis = false,
        $usediagrams = false,
        $plan = null,
        $counts = []
    ) {
        global $USER;

        $counts = !empty($counts) ? $counts : self::resolveCounts([]);

        // Build prompt for AI.
        $prompt = $this->buildGenerationPrompt(
            $topic,
            $level,
            $numsections,
            $includequiz,
            $includeassignment,
            $uploadedcontent,
            $customtitle,
            $useemojis,
            $usediagrams,
            $plan,
            $counts
        );

        $this->writeProgress(1, 15, 'Generating your course with AI — this can take a few minutes...');

        // Build grouped list: [{providerid, providername, models:[...]}]
        // Order: requested/default provider first, then other enabled providers.
        $providers = $this->buildFallbackProviders($providerid, $model);

        $lasterror   = null;
        $fallbacklog = [];
        $attemptindex = 0;

        foreach ($providers as $prov) {
            $ratelimited = false;
            foreach ($prov['models'] as $trymodel) {
                for ($retry = 1; $retry <= 3; $retry++) {
                    try {
                        $attemptindex++;
                        $response = provider::callApi($prompt, $prov['providerid'], $trymodel);

                        $this->writeProgress(2, 65, 'AI response received, processing...');

                        // Strip markdown code fences.
                        $rawresponse = trim($response);
                        $rawresponse = preg_replace('/^```(?:json)?\s*\n?/i', '', $rawresponse);
                        $rawresponse = preg_replace('/\n?\s*```\s*$/', '', $rawresponse);
                        $rawresponse = trim($rawresponse);

                        debugging('[CourseAgent] Raw AI response (attempt ' . $attemptindex . '): ' . substr($rawresponse, 0, 3000));

                        // Remove illegal control chars (0x00-0x08, 0x0B, 0x0C, 0x0E-0x1F).
                        $response = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $rawresponse);
                        // Escape bare CR/LF/TAB inside JSON string values.
                        ini_set('pcre.backtrack_limit', 10000000);
                        $sanitized = preg_replace_callback('/"((?:[^"\\\\]|\\\\.)*)"\/s', function ($m) {
                            $inner = $m[1];
                            $inner = preg_replace('/(?<!\\\\)\r/', '\\r', $inner);
                            $inner = preg_replace('/(?<!\\\\)\n/', '\\n', $inner);
                            $inner = preg_replace('/(?<!\\\\)\t/', '\\t', $inner);
                            return '"' . $inner . '"';
                        }, $response);
                        if ($sanitized !== null) {
                            $response = $sanitized;
                        }

                        debugging('[CourseAgent] Sanitized response (attempt ' . $attemptindex . '): ' . substr($response, 0, 3000));

                        $coursedata = json_decode($response);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $coursedata = json_decode($response, false, 512, JSON_INVALID_UTF8_IGNORE);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $debugpreview = substr($response, 0, 2000);
                                debugging('[CourseAgent] JSON parse FAILED (attempt ' . $attemptindex . '). Error: ' . json_last_error_msg() . ' | Preview: ' . $debugpreview);
                                throw new \Exception(
                                    'Failed to parse AI response as JSON: ' . json_last_error_msg() .
                                    "\n\n--- RAW AI RESPONSE (first 2000 chars) ---\n" . $debugpreview .
                                    "\n--- END RAW AI RESPONSE ---"
                                );
                            }
                        }
                        if (empty($coursedata->title) || empty($coursedata->sections)) {
                            throw new \Exception('AI returned incomplete course structure. Missing title or sections.');
                        }
                        $returnedsectioncount = count($coursedata->sections);
                        if ($returnedsectioncount != $numsections) {
                            throw new \Exception(
                                "AI returned {$returnedsectioncount} sections but {$numsections} were requested. " .
                                "Rejecting and trying fallback."
                            );
                        }

                        $coursedata->_used_provider_id   = $prov['providerid'];
                        $coursedata->_used_provider_name = $prov['providername'];
                        $coursedata->_used_model         = $trymodel;
                        $coursedata->_fallback_log        = $fallbacklog;

                        foreach ($coursedata->sections as $i => $section) {
                            if (!empty($section->name)) {
                                $section->name = preg_replace('/^Section\s+\d+[:.\s]*/i', '', $section->name);
                            }
                            // Stamp plan decisions onto each section. The plan may use the new
                            // count fields (quiz_count / assignment_count / h5p_types[]) or the
                            // older boolean/single-type shape — support both.
                            if ($plan && isset($plan->sections[$i])) {
                                $plansec = $plan->sections[$i];
                                if (isset($plansec->quiz_count)) {
                                    $section->quiz_count_planned = (int) $plansec->quiz_count;
                                } elseif (isset($plansec->quiz)) {
                                    $section->quiz_count_planned = !empty($plansec->quiz) ? 1 : 0;
                                }
                                if (isset($plansec->assignment_count)) {
                                    $section->assignment_count_planned = (int) $plansec->assignment_count;
                                } elseif (isset($plansec->assignment)) {
                                    $section->assignment_count_planned = !empty($plansec->assignment) ? 1 : 0;
                                }
                                if (isset($plansec->h5p_types) && is_array($plansec->h5p_types)) {
                                    $section->h5p = array_values($plansec->h5p_types);
                                } elseif (!empty($plansec->h5p_type)) {
                                    $section->h5p = [$plansec->h5p_type];
                                }
                                // Keep the legacy single-type field populated for any older readers.
                                if (!empty($section->h5p) && is_array($section->h5p)) {
                                    $section->h5p_type = $section->h5p[0];
                                }
                            }

                            // Ensure quizzes/assignments/h5p are arrays, then point the legacy
                            // singular fields at the FIRST item (same object handle) so the
                            // preview's conversational edit flow keeps working on item 0.
                            $section = $this->normalizeSection($section);
                            $section->quiz       = $section->quizzes[0] ?? null;
                            $section->assignment = $section->assignments[0] ?? null;
                        }

                        return $coursedata;
                    } catch (\Exception $e) {
                        $lasterror   = $e->getMessage();
                        $isratelimit = $this->isRateLimitError($lasterror);
                        $fallbacklog[] = [
                            'provider' => $prov['providername'],
                            'model'    => $trymodel ?: '(default)',
                            'retry'    => $retry,
                            'reason'   => $isratelimit ? 'rate_limit' : 'error',
                            'message'  => $lasterror,
                        ];
                        if ($isratelimit) {
                            $ratelimited = true;
                            break 2; // Skip remaining models of this provider.
                        }
                        // JSON/other error: retry up to 3x then move to next model.
                    }
                }
            }
            // $ratelimited=true → foreach continues to next provider automatically.
        }

        throw new \Exception('All AI providers exhausted after retries. Last error: ' . $lasterror);
    }

    /**
     * Generate a lightweight course plan — structure only, no lesson content or quiz questions.
     * Used by paid users (saas_api_key set) before full generation. The plan contains per-section
     * curriculum decisions: which activities make sense and which H5P type fits best.
     *
     * @param string      $topic            Course topic
     * @param string      $level            Difficulty level
     * @param int         $numsections      Number of sections
     * @param bool        $includequiz      Quiz toggle
     * @param bool        $includeassignment Assignment toggle
     * @param bool        $includeh5p       H5P toggle
     * @param int|null    $providerid       Provider ID
     * @param string|null $model            Model name
     * @param string|null $uploadedcontent  Extracted document text
     * @param string|null $customtitle      Custom title override
     * @return \stdClass Plan object {title, summary, sections[]}
     */
    public function planCourseOutline(
        $topic,
        $level,
        $numsections,
        $includequiz = true,
        $includeassignment = false,
        $includeh5p = false,
        $providerid = null,
        $model = null,
        $uploadedcontent = null,
        $customtitle = null,
        $h5ptypes = '',
        $counts = []
    ) {
        $counts = !empty($counts) ? $counts : self::resolveCounts([]);
        $allvalidtypes = [
            'single_choice_set', 'summary', 'drag_the_words',
            'multiple_choice', 'true_false', 'fill_in_blanks',
            'quiz_question_set', 'dialog_cards', 'essay',
            'mark_the_words', 'sort_the_paragraphs', 'crossword',
            'find_the_words', 'accordion', 'personality_quiz', 'chart', 'timeline',
        ];
        $allowedtypes = $allvalidtypes;
        if (!empty($h5ptypes)) {
            $parsed = array_values(array_filter(
                array_map('trim', explode(',', $h5ptypes)),
                fn($t) => in_array($t, $allvalidtypes, true)
            ));
            if (!empty($parsed)) {
                $allowedtypes = $parsed;
            }
        }

        $prompt = $this->buildPlanPrompt(
            $topic,
            $level,
            $numsections,
            $includequiz,
            $includeassignment,
            $includeh5p,
            $uploadedcontent,
            $customtitle,
            $allowedtypes,
            $counts
        );

        $providers  = $this->buildFallbackProviders($providerid, $model);
        $lasterror  = null;
        $fallbacklog = [];

        foreach ($providers as $prov) {
            $ratelimited = false;
            foreach ($prov['models'] as $trymodel) {
                for ($retry = 1; $retry <= 3; $retry++) {
                    try {
                        $response = provider::callApi($prompt, $prov['providerid'], $trymodel);

                        $rawresponse = trim($response);
                        $rawresponse = preg_replace('/^```(?:json)?\s*\n?/i', '', $rawresponse);
                        $rawresponse = preg_replace('/\n?\s*```\s*$/', '', $rawresponse);
                        $rawresponse = trim($rawresponse);
                        $rawresponse = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $rawresponse);

                        $plandata = json_decode($rawresponse);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $plandata = json_decode($rawresponse, false, 512, JSON_INVALID_UTF8_IGNORE);
                        }
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception('Failed to parse plan JSON: ' . json_last_error_msg());
                        }
                        if (empty($plandata->title) || empty($plandata->sections)) {
                            throw new \Exception('Plan response missing title or sections.');
                        }
                        $returned = count($plandata->sections);
                        if ($returned != $numsections) {
                            throw new \Exception("Plan returned {$returned} sections, expected {$numsections}.");
                        }

                        foreach ($plandata->sections as $sec) {
                            if (!empty($sec->name)) {
                                $sec->name = preg_replace('/^Section\s+\d+[:.\s]*/i', '', $sec->name);
                            }
                        }

                        return $plandata;
                    } catch (\Exception $e) {
                        $lasterror   = $e->getMessage();
                        $isratelimit = $this->isRateLimitError($lasterror);
                        $fallbacklog[] = [
                            'provider' => $prov['providername'],
                            'model'    => $trymodel ?: '(default)',
                            'retry'    => $retry,
                            'reason'   => $isratelimit ? 'rate_limit' : 'error',
                            'message'  => $lasterror,
                        ];
                        if ($isratelimit) {
                            $ratelimited = true;
                            break 2;
                        }
                    }
                }
            }
        }

        throw new \Exception('All AI providers exhausted during plan step. Last error: ' . $lasterror);
    }

    /**
     * Build the lightweight planning prompt.
     */
    private function buildPlanPrompt(
        $topic,
        $level,
        $numsections,
        $includequiz,
        $includeassignment,
        $includeh5p,
        $uploadedcontent = null,
        $customtitle = null,
        $allowedtypes = [],
        $counts = []
    ) {
        $counts = !empty($counts) ? $counts : self::resolveCounts([]);
        $topicline = !empty($topic) ? "Topic: {$topic}" : 'Topic: (derive from source document)';
        $titleline = !empty($customtitle) ? "Custom title requested: \"{$customtitle}\"" : '';

        $contextsection = '';
        if (!empty($uploadedcontent)) {
            $snippet = mb_substr($uploadedcontent, 0, 4000);
            $contextsection = "\n\nSOURCE DOCUMENT (use to inform section topics):\n--- BEGIN ---\n" . $snippet . "\n--- END ---\n";
        }

        $enabledlist   = "- Lesson: always included\n";
        $enabledlist  .= '- Quiz (MCQ): ' . ($includequiz ? 'enabled' : 'disabled') . "\n";
        $enabledlist  .= '- Assignment: ' . ($includeassignment ? 'enabled' : 'disabled') . "\n";
        $enabledlist  .= '- H5P Interactive Activity: ' . ($includeh5p ? 'enabled' : 'disabled') . "\n";

        // How many of each activity the teacher wants (per-section range and/or whole-course cap).
        $countrules  = $this->countInstruction('Quizzes', $counts['quiz'], $includequiz);
        $countrules .= $this->countInstruction('Assignments', $counts['assignment'], $includeassignment);
        $countrules .= $this->countInstruction('H5P interactive activities', $counts['h5p'], $includeh5p);
        $countsection = $countrules !== ''
            ? "\nHOW MANY OF EACH ACTIVITY (decide a count per section within these limits):\n" . $countrules
            : '';

        $h5pinstruction = '';
        if ($includeh5p) {
            $typedesc = [
                'single_choice_set'   => 'set of MCQ questions testing factual recall',
                'summary'             => 'students identify the correct statement in each group',
                'drag_the_words'      => 'drag key terms into blanks in sentences',
                'multiple_choice'     => 'single MCQ question with one correct answer',
                'true_false'          => 'single true/false statement to evaluate',
                'fill_in_blanks'      => 'type missing key words into blank spaces',
                'quiz_question_set'   => 'full quiz with 5-8 multiple choice questions',
                'dialog_cards'        => 'flashcard pairs with term on front, definition on back',
                'essay'               => 'open-ended prompt with keyword hints for self-assessment',
                'mark_the_words'      => 'click on key terms highlighted in a paragraph',
                'sort_the_paragraphs' => 'reorder shuffled paragraphs into correct sequence',
                'crossword'           => 'fill in answers to across/down clues',
                'find_the_words'      => 'find hidden key terms in a word search grid',
                'accordion'           => 'expandable sections with detailed text content',
                'personality_quiz'    => 'questions that match learner to a learning profile',
                'chart'               => 'bar chart comparing key quantities or categories from the material',
                'timeline'            => 'chronological sequence of events or milestones',
            ];
            $typelist = !empty($allowedtypes) ? $allowedtypes : array_keys($typedesc);
            $h5pinstruction = "\nFor H5P (when enabled), choose the BEST type for each section from:\n";
            foreach ($typelist as $t) {
                if (isset($typedesc[$t])) {
                    $h5pinstruction .= "  - {$t}: {$typedesc[$t]}\n";
                }
            }
            $h5pinstruction .= "Set h5p_type to null if none of the types fits well for a section.\n"
                . "Always set h5p_reason: a one-sentence explanation of why you chose that type.\n";
        }

        $titlefield = !empty($customtitle)
            ? '"title": "' . addslashes($customtitle) . '"'
            : '"title": "Descriptive course title"';

        $prompt  = "You are an expert curriculum designer.\n";
        $prompt .= "Plan the structure of a Moodle course. Return JSON ONLY — no markdown, no explanation.\n\n";
        $prompt .= "{$topicline}\n";
        if ($titleline) {
            $prompt .= "{$titleline}\n";
        }
        $prompt .= "Level: {$level}\n";
        $prompt .= "Number of sections: EXACTLY {$numsections}\n";
        $prompt .= $contextsection;
        $prompt .= "\n\nACTIVITIES ENABLED BY THE TEACHER:\n" . $enabledlist;
        $prompt .= $countsection;
        $prompt .= "\nFor EACH section, decide HOW MANY of each enabled activity make sense given that section's content, staying within the limits above.\n";
        $prompt .= "A section doesn't need the maximum of every activity — use judgment, but respect the per-section range and never exceed the whole-course total.\n";
        $prompt .= "E.g. an intro section may need 0 assignments; a vocab-heavy section suits drag-the-words.\n";
        $prompt .= $h5pinstruction;
        $prompt .= "\nDo NOT generate lesson content, quiz questions, or assignment instructions.\n";
        $prompt .= "Return ONLY this JSON structure (repeat the section object exactly {$numsections} times):\n\n";
        $prompt .= "{\n";
        $prompt .= "  {$titlefield},\n";
        $prompt .= '  "summary": "2-3 sentence course description",' . "\n";
        $prompt .= '  "sections": [' . "\n";
        $prompt .= "    {\n";
        $prompt .= '      "name": "Section title (no numbering)",' . "\n";
        $prompt .= '      "description": "2-3 sentence section overview",' . "\n";
        $prompt .= '      "lesson": true,' . "\n";
        if ($includequiz) {
            $prompt .= '      "quiz_count": 1,' . "  // how many quizzes this section should have (0 if none fits)\n";
        }
        if ($includeassignment) {
            $prompt .= '      "assignment_count": 0,' . "  // how many assignments this section should have (0 if none fits)\n";
        }
        if ($includeh5p) {
            $firsttype = !empty($typelist) ? $typelist[0] : 'single_choice_set';
            $othertypes = !empty($typelist) ? implode(', ', $typelist) : 'single_choice_set';
            $prompt .= '      "h5p_types": ["' . $firsttype . '"],' . "  // array of chosen types (one entry per H5P activity for this section); pick from: {$othertypes}; use [] for none\n";
            $prompt .= '      "h5p_reason": "One sentence explaining the chosen H5P type(s)"' . "\n";
        }
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}\n";
        $prompt .= "\nIMPORTANT: sections array must contain exactly {$numsections} objects. Return valid JSON only.";

        return $prompt;
    }

    /**
     * Build ordered list of providers with their models for the retry loop.
     * Returns [{providerid, providername, models:[...]}].
     * Order: requested/default provider first, then other enabled providers.
     *
     * @param int|null    $providerid Requested provider ID
     * @param string|null $model      Requested model (placed first in its provider's list)
     * @return array
     */
    private function buildFallbackProviders(?int $providerid, ?string $model): array
    {
        $allproviders = provider::getAll(true);
        $result       = [];
        $seen         = [];

        $addprovider = function ($p, $firstmodel) use (&$result, &$seen) {
            if (isset($seen[$p->id])) {
                return;
            }
            $seen[$p->id] = true;
            $models = json_decode($p->models, true) ?: [];
            if ($firstmodel && in_array($firstmodel, $models, true)) {
                $models = array_merge([$firstmodel], array_values(array_filter($models, function ($m) use ($firstmodel) {
                    return $m !== $firstmodel;
                })));
            }
            if ($models) {
                $result[] = ['providerid' => $p->id, 'providername' => $p->name, 'models' => $models];
            }
        };

        if ($providerid) {
            $req = provider::get($providerid);
            if ($req) {
                $addprovider($req, $model);
            }
        } else {
            $def = provider::getDefault();
            if ($def) {
                $addprovider($def, $model);
            }
        }

        foreach ($allproviders as $p) {
            $addprovider($p, null);
        }

        return $result;
    }

    /**
     * Determine whether an exception message indicates a rate limit.
     *
     * @param string $message Error message
     * @return bool
     */
    private function isRateLimitError(string $message): bool
    {
        $patterns = [
            'rate limit', 'rate_limit', 'ratelimit',
            '429', 'too many requests',
            'quota exceeded', 'quota_exceeded',
            'resource_exhausted', 'resource exhausted',
            'tokens per', 'requests per',
            'model_rate_limit',
        ];
        $lower = strtolower($message);
        foreach ($patterns as $p) {
            if (strpos($lower, $p) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build prompt for course generation.
     */
    private function buildGenerationPrompt(
        $topic,
        $level,
        $numsections,
        $includequiz,
        $includeassignment,
        $uploadedcontent = null,
        $customtitle = null,
        $useemojis = false,
        $usediagrams = false,
        $plan = null,
        $counts = []
    ) {
        $counts = !empty($counts) ? $counts : self::resolveCounts([]);
        $maxsections = get_config('local_courseagent', 'max_sections') ?: 8;
        $maxquiz     = get_config('local_courseagent', 'max_quiz_questions') ?: 7;

        // Build context section.
        $contextsection = '';
        if (!empty($uploadedcontent)) {
            $snippet = mb_substr($uploadedcontent, 0, 8000);
            $contextsection = "\n\nSOURCE DOCUMENT CONTENT (use this as the primary knowledge base for the course):\n"
                . "--- BEGIN DOCUMENT ---\n"
                . $snippet
                . (mb_strlen($uploadedcontent) > 8000 ? '\n[... document continues ...]' : '')
                . "\n--- END DOCUMENT ---\n";
        }

        // Title instruction.
        $titleinstruction = !empty($customtitle)
            ? '"title": "' . addslashes($customtitle) . '"'
            : '"title": "A descriptive, engaging course title derived from the topic/content"';

        // Topic line.
        $topicline = !empty($topic) ? "Topic: {$topic}" : 'Topic: (derive from the source document above)';

        $quizcount = min($maxquiz, 5); // Questions inside EACH quiz (the slider controls how many quizzes, not questions).

        // Count rules: how many quizzes / assignments per section and/or across the course.
        $countrules  = $this->countInstruction('Quizzes', $counts['quiz'], $includequiz);
        $countrules .= $this->countInstruction('Assignments', $counts['assignment'], $includeassignment);

        $prompt  = "You are a senior instructional designer with expertise in creating comprehensive, university-level online courses.\n";
        $prompt .= "Your task is to create a COMPLETE, DETAILED course — not a brief outline.\n\n";
        $prompt .= "{$topicline}\n";
        $prompt .= "Level: {$level}\n";
        $prompt .= "Number of Sections: {$numsections} (maximum {$maxsections})\n";
        $prompt .= 'Include Quizzes: ' . ($includequiz ? 'Yes' : 'No') . "\n";
        $prompt .= 'Include Assignments: ' . ($includeassignment ? 'Yes' : 'No') . "\n";
        if ($countrules !== '') {
            $prompt .= "\nHOW MANY OF EACH ACTIVITY (each section's array must hold this many items):\n" . $countrules;
        }
        $prompt .= $contextsection;

        // Approved course plan — AI must follow this structure exactly.
        if (!empty($plan) && !empty($plan->sections)) {
            $prompt .= "\n\n== APPROVED COURSE PLAN — FOLLOW THIS STRUCTURE EXACTLY ==\n";
            $prompt .= "The teacher has approved the following plan. ";
            $prompt .= "Use the EXACT section names and generate content for ONLY the activities listed per section.\n\n";
            foreach ($plan->sections as $si => $plansec) {
                $snum = $si + 1;
                $secname = $plansec->name ?? "Section {$snum}";
                // Support both the new count fields and the older boolean shape.
                $qc = isset($plansec->quiz_count) ? (int) $plansec->quiz_count
                    : (!empty($plansec->quiz) ? 1 : 0);
                $ac = isset($plansec->assignment_count) ? (int) $plansec->assignment_count
                    : (!empty($plansec->assignment) ? 1 : 0);
                $activities = ['Lesson (always)'];
                if ($qc > 0) {
                    $activities[] = $qc . ' Quiz' . ($qc > 1 ? 'zes' : '');
                }
                if ($ac > 0) {
                    $activities[] = $ac . ' Assignment' . ($ac > 1 ? 's' : '');
                }
                $prompt .= "  Section {$snum}: \"{$secname}\"\n";
                $prompt .= "    Activities: " . implode(', ', $activities) . "\n";
            }
            $prompt .= "\nDo NOT rename, reorder, or add sections beyond this plan. ";
            $prompt .= "Put exactly the listed number of quizzes in each section's \"quizzes\" array, ";
            $prompt .= "and the listed number of assignments in each section's \"assignments\" array.\n";
        }

        $prompt .= "\n\n== CRITICAL CONTENT REQUIREMENTS ==\n";
        $prompt .= "For EVERY section, you MUST write a COMPLETE, DETAILED lesson with ALL of the following:\n";
        $prompt .= "  1. An introduction paragraph (2-3 sentences) explaining what learners will discover.\n";
        $prompt .= "  2. At least 3-5 main concept headings, each with 2-4 paragraphs of explanation.\n";
        $prompt .= "  3. Concrete real-world examples and use-cases for each concept.\n";
        $prompt .= "  4. A 'Key Takeaways' section with 5-7 bullet points.\n";
        $prompt .= "  5. A 'Further Reading / Practice' section with suggestions.\n";
        $prompt .= "The content_html field MUST contain well-structured HTML with ";
        $prompt .= "<h2>, <h3>, <p>, <ul>, <ol>, <strong>, <em>, <blockquote>, ";
        $prompt .= "and <pre><code> tags as appropriate.\n";
        $prompt .= "Each lesson MUST be at minimum 800 words — comprehensive enough for a student to learn the topic without any other resources.\n\n";

        // Emoji styling instructions.
        if ($useemojis) {
            $prompt .= "== EMOJI ENHANCEMENTS ==\n";
            $prompt .= "Sprinkle relevant emojis throughout the content to make it engaging and visually appealing.\n";
            $prompt .= "Use emojis in headings, bullet points, and key concepts where appropriate.\n";
            $prompt .= "Examples: 📚 for learning, 💡 for tips, 🎯 for objectives, ⚠️ for warnings, ✅ for checklists, 📝 for examples.\n\n";
        }

        // Mermaid diagram instructions.
        if ($usediagrams) {
            $prompt .= "== MERMAID DIAGRAMS (v11.15 — strict syntax required) ==\n";
            $prompt .= "Where a visual diagram genuinely helps understanding, include ONE Mermaid.js diagram in the section content_html.\n";
            $prompt .= "Embed ONLY as a raw <div class=\"mermaid\"> block. NEVER add ```mermaid fences inside the div.\n\n";

            $prompt .= "DIAGRAM TYPE SELECTION — choose the single best match for the content:\n\n";

            $prompt .= "TIER 1 — STABLE (prefer these, lowest syntax error risk):\n";
            $prompt .= "  flowchart TD|LR|BT|RL  — processes, workflows, decision trees, pipelines\n";
            $prompt .= "  sequenceDiagram         — step-by-step interactions between systems or people\n";
            $prompt .= "  classDiagram            — OOP class structures, data models, inheritance\n";
            $prompt .= "  stateDiagram-v2         — state machines, lifecycle flows, mode transitions\n";
            $prompt .= "  erDiagram               — database schemas, entity relationships\n";
            $prompt .= "  pie title X             — proportional breakdowns, percentages\n";
            $prompt .= "  gantt                   — project schedules, timelines, phases\n";
            $prompt .= "  gitGraph                — branching strategies, Git workflows\n";
            $prompt .= "  mindmap                 — concept maps, topic hierarchies (indentation-based)\n";
            $prompt .= "  timeline                — historical sequences, chronological events\n\n";

            $prompt .= "TIER 2 — SUPPORTED (use when content is a strong match):\n";
            $prompt .= "  journey                 — user experience flows, step-by-step journeys with scores\n";
            $prompt .= "  quadrantChart           — 2x2 prioritisation matrices, effort/impact grids\n";
            $prompt .= "  requirementDiagram      — system requirements, traceability matrices\n";
            $prompt .= "  xychart-beta            — bar or line charts with numeric axes\n";
            $prompt .= "  sankey-beta             — flow/resource distribution between nodes\n";
            $prompt .= "  C4Context               — high-level software architecture (persons, systems)\n\n";

            $prompt .= "TIER 3 — EXPERIMENTAL (only when a perfect fit, syntax is strict and fragile):\n";
            $prompt .= "  block-beta              — visual block/layer diagrams with explicit layout\n";
            $prompt .= "  packet-beta             — network packet structures, binary field layouts\n";
            $prompt .= "  kanban                  — task boards with columns and cards\n";
            $prompt .= "  architecture-beta       — infrastructure diagrams with services and groups\n";
            $prompt .= "  zenuml                  — UML sequence diagrams with code-like syntax\n\n";

            $prompt .= "SYNTAX EXAMPLES for the error-prone types:\n\n";

            $prompt .= "gantt — MUST include dateFormat before any section:\n";
            $prompt .= '<div class="mermaid">gantt' . "\n";
            $prompt .= "    title Project Plan\n";
            $prompt .= "    dateFormat YYYY-MM-DD\n";
            $prompt .= "    section Phase 1\n";
            $prompt .= "    Task A :a1, 2024-01-01, 7d\n";
            $prompt .= "    Task B :a2, after a1, 5d\n";
            $prompt .= "</div>\n\n";

            $prompt .= "mindmap — indentation defines hierarchy, NO arrows or brackets on root:\n";
            $prompt .= '<div class="mermaid">mindmap' . "\n";
            $prompt .= "  root((Main Topic))\n";
            $prompt .= "    Branch A\n";
            $prompt .= "      Leaf 1\n";
            $prompt .= "      Leaf 2\n";
            $prompt .= "    Branch B\n";
            $prompt .= "      Leaf 3\n";
            $prompt .= "</div>\n\n";

            $prompt .= "timeline — section keyword required, each event colon-separated on its own line:\n";
            $prompt .= '<div class="mermaid">timeline' . "\n";
            $prompt .= "    title History of Technology\n";
            $prompt .= "    section 1990s\n";
            $prompt .= "        1991 : World Wide Web\n";
            $prompt .= "        1995 : JavaScript\n";
            $prompt .= "    section 2000s\n";
            $prompt .= "        2004 : Facebook\n";
            $prompt .= "</div>\n\n";

            $prompt .= "xychart-beta — axis values must be arrays or ranges, title in double-quotes:\n";
            $prompt .= '<div class="mermaid">xychart-beta' . "\n";
            $prompt .= '    title "Quarterly Revenue"' . "\n";
            $prompt .= '    x-axis ["Q1", "Q2", "Q3", "Q4"]' . "\n";
            $prompt .= "    y-axis 0 --> 100\n";
            $prompt .= "    bar [40, 65, 55, 80]\n";
            $prompt .= "</div>\n\n";

            $prompt .= "quadrantChart — axis labels and [x,y] point syntax required:\n";
            $prompt .= '<div class="mermaid">quadrantChart' . "\n";
            $prompt .= '    title "Effort vs Impact"' . "\n";
            $prompt .= "    x-axis Low Effort --> High Effort\n";
            $prompt .= "    y-axis Low Impact --> High Impact\n";
            $prompt .= '    Task A: [0.3, 0.8]' . "\n";
            $prompt .= '    Task B: [0.7, 0.4]' . "\n";
            $prompt .= "</div>\n\n";

            $prompt .= "sankey-beta — CSV rows: Source,Target,Value (no spaces around commas):\n";
            $prompt .= '<div class="mermaid">sankey-beta' . "\n";
            $prompt .= "    Revenue,Operations,40\n";
            $prompt .= "    Revenue,Marketing,30\n";
            $prompt .= "    Revenue,RnD,30\n";
            $prompt .= "</div>\n\n";

            $prompt .= "kanban — column IDs with quoted labels, cards nested inside:\n";
            $prompt .= '<div class="mermaid">kanban' . "\n";
            $prompt .= '    todo["To Do"]' . "\n";
            $prompt .= '        id1["Write tests"]' . "\n";
            $prompt .= '        id2["Update docs"]' . "\n";
            $prompt .= '    doing["In Progress"]' . "\n";
            $prompt .= '        id3["Build API"]' . "\n";
            $prompt .= '    done["Done"]' . "\n";
            $prompt .= '        id4["Design mockups"]' . "\n";
            $prompt .= "</div>\n\n";

            $prompt .= "journey — title + section required, each task: label: score: Actor:\n";
            $prompt .= '<div class="mermaid">journey' . "\n";
            $prompt .= "    title User Onboarding\n";
            $prompt .= "    section Sign Up\n";
            $prompt .= "        Visit landing page: 5: User\n";
            $prompt .= "        Fill in form: 3: User\n";
            $prompt .= "        Confirm email: 4: User, System\n";
            $prompt .= "</div>\n\n";

            $prompt .= "C4Context — use only Person(), System(), SystemDb(), Rel() — no custom shapes:\n";
            $prompt .= '<div class="mermaid">C4Context' . "\n";
            $prompt .= '    Person(user, "Customer", "Uses the app")' . "\n";
            $prompt .= '    System(app, "Web App", "Core platform")' . "\n";
            $prompt .= '    SystemDb(db, "Database", "Stores data")' . "\n";
            $prompt .= '    Rel(user, app, "Uses")' . "\n";
            $prompt .= '    Rel(app, db, "Reads/Writes")' . "\n";
            $prompt .= "</div>\n\n";

            $prompt .= "UNIVERSAL SYNTAX RULES — any violation causes a broken diagram:\n";
            $prompt .= "  1. LABEL QUOTING: wrap labels in double-quotes if they contain comma , colon : parentheses () semicolon ; slash / ampersand & or dash -\n";
            $prompt .= "     Correct: A[\"Process data\"] -->|\"if valid\"| B[\"Save record\"]\n";
            $prompt .= "     Wrong:   A[Process data] -->|if valid| B[Save record]\n";
            $prompt .= "  2. NO markdown code fences (``` or ```mermaid) inside the <div class=\"mermaid\"> block.\n";
            $prompt .= "  3. NO HTML tags (<br> <b> <i> etc.) inside any label or node.\n";
            $prompt .= "  4. NO semicolons at line endings.\n";
            $prompt .= "  5. sequenceDiagram: participant names with spaces MUST be aliased: participant WS as \"Web Server\"\n";
            $prompt .= "  6. classDiagram: method/attribute lines use +/-/# prefix with no spaces before the name.\n";
            $prompt .= "  7. C4Context: only use Person(), System(), SystemDb(), Container(), Rel() shapes.\n";
            $prompt .= "  8. Max 10-12 nodes/items/rows. Deep nesting or large datasets break the parser.\n";
            $prompt .= "  9. Prefer Tier 1 types. Only use Tier 2/3 when genuinely a better fit for the content.\n";
            $prompt .= "  10. ONE diagram per section maximum. Skip entirely if no diagram genuinely aids the content.\n\n";
        }

        if ($includequiz) {
            $prompt .= "== MCQ QUIZ REQUIREMENTS ==\n";
            $prompt .= "Each section's \"quizzes\" array holds the number of quizzes set in the count rules above (it may be empty for a section).\n";
            $prompt .= "Each quiz MUST contain exactly {$quizcount} multiple-choice questions that test deep understanding. When a section has more than one quiz, each must cover DIFFERENT material.\n";
            $prompt .= "Each question MUST have exactly 4 answer options (A, B, C, D).\n";
            $prompt .= "correct_answer is the 0-based index of the correct option (0=A, 1=B, 2=C, 3=D).\n";
            $prompt .= "Mix question types: factual recall, conceptual understanding, and application.\n\n";
        }

        if ($includeassignment) {
            $prompt .= "== ASSIGNMENT REQUIREMENTS ==\n";
            $prompt .= "Each section's \"assignments\" array holds the number of assignments set in the count rules above (it may be empty for a section).\n";
            $prompt .= "Each assignment reinforces the lesson content and has clear instructions, a descriptive title, and an estimated word count. When a section has more than one assignment, each must tackle a DIFFERENT task.\n";
            $prompt .= "Each instruction step must be plain text — do NOT number the steps yourself and do NOT use markdown symbols like ** or backticks.\n";
            $prompt .= "Do NOT reference any external files, datasets, CSVs, PDFs, or downloadable resources in assignment instructions. All tasks must be completable by the student using only their own knowledge and publicly available information.\n\n";
        }

        $prompt .= "== CRITICAL SECTION COUNT REQUIREMENT ==\n";
        $prompt .= "You MUST generate EXACTLY {$numsections} sections.\n";
        $prompt .= "The 'sections' array in your JSON response MUST contain precisely {$numsections} section objects — no more, no less.\n";
        $prompt .= "Do not stop early. Do not return fewer sections than requested.\n\n";

        $prompt .= "Return ONLY a valid JSON object — no markdown fences, no extra text before or after.\n";
        $prompt .= "Use this EXACT JSON structure (repeat the section template exactly {$numsections} times):\n";
        $prompt .= "{\n";
        $prompt .= "  {$titleinstruction},\n";
        $prompt .= '  "summary": "A rich 2-3 sentence course description explaining what students will learn and why it matters",' . "\n";
        $prompt .= '  "sections": [' . "\n";
        $prompt .= "    {\n";
        $prompt .= '      "name": "Clear descriptive title — do NOT start with Section N: or any numbering",' . "\n";
        $prompt .= '      "description": "2-3 sentence section overview",' . "\n";
        $prompt .= "      \"lesson\": {\n";
        $prompt .= '        "summary": "1-2 sentence lesson intro shown to students before they open the lesson",' . "\n";
        $prompt .= '        "content_html": "<h2>Introduction</h2><p>...</p>';
        $prompt .= '<h2>Core Concept 1</h2><p>...</p><h3>Example</h3><p>...</p>';
        $prompt .= '<h2>Core Concept 2</h2><p>...</p><h2>Key Takeaways</h2>';
        $prompt .= '<ul><li>...</li></ul><h2>Further Reading</h2><p>...</p>"' . "\n";
        $prompt .= "      }";

        if ($includequiz) {
            $prompt .= ",\n      \"quizzes\": [\n";
            $prompt .= "        {\n";
            $prompt .= "          \"name\": \"Section Quiz\",\n";
            $prompt .= '          "questions": [' . "\n";
            $prompt .= "            {\n";
            $prompt .= '              "question": "Full question text ending with a question mark?",' . "\n";
            $prompt .= '              "options": ["Option A text", "Option B text", "Option C text", "Option D text"],' . "\n";
            $prompt .= '              "correct_answer": 0,' . "  // 0-based index\n";
            $prompt .= '              "explanation": "Brief explanation of why this answer is correct",' . "\n";
            $prompt .= '              "points": 1' . "\n";
            $prompt .= "            }\n";
            $prompt .= "          ]\n";
            $prompt .= "        }\n";
            $prompt .= "      ]";
        }

        if ($includeassignment) {
            $prompt .= ",\n      \"assignments\": [\n";
            $prompt .= "        {\n";
            $prompt .= '          "title": "Descriptive assignment title (e.g., \'Research and Analysis Essay\' or \'Code Implementation Task\')",' . "\n";
            $prompt .= '          "description": "2-3 sentence description of what the student needs to do and the learning objective",' . "\n";
            $prompt .= '          "instructions": ["Clear first step the student should follow (plain text, no leading number, no markdown)", "Second step with specific requirements", "Final step with submission guidelines"],' . "\n";
            $prompt .= '          "word_count": 500' . "\n";
            $prompt .= "        }\n";
            $prompt .= "      ]";
        }

        $prompt .= "\n    }\n  ]\n}";

        $prompt .= "\n\nREMEMBER: Every lesson content_html must be thorough — at least 800 words of educational content.";
        $prompt .= " \"quizzes\" and \"assignments\" are ARRAYS — put the number of items set by the count rules in each (an empty array [] is allowed for a section).";
        $prompt .= " Every quiz must have exactly {$quizcount} MCQ questions.";
        $prompt .= " Return valid JSON only — no surrounding markdown.";

        return $prompt;
    }



    /**
     * Publish course to Moodle.
     *
     * @param \stdClass $coursedata Course data
     * @return array{courseid: int, h5p_warnings: string[]}
     */
    public function publishCourse($coursedata)
    {
        global $DB, $USER;

        // Validate course data.
        if (empty($coursedata->title)) {
            throw new \Exception('Course title is required');
        }
        if (empty($coursedata->sections) || !is_array($coursedata->sections)) {
            throw new \Exception('Course must have sections');
        }

        $coursename  = $coursedata->title;
        $numsections = count($coursedata->sections);
        $shortname   = substr(
            preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', strtolower($coursename))),
            0,
            50
        ) . '_' . time();

        // Create course via core external API (recommended for local plugins).
        // Note: numsections courseformatoption was removed in Moodle 4.0+.
        // We create sections explicitly after course creation.
        $newcourses = \core_course_external::create_courses([[
            'fullname'      => $coursename,
            'shortname'     => $shortname,
            'categoryid'    => 1,
            'summary'       => !empty($coursedata->summary) ? $coursedata->summary : '',
            'summaryformat' => FORMAT_HTML,
            'format'        => 'topics',
            'visible'       => 0,
        ]]);
        $courseid = $newcourses[0]['id'];
        $course   = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        // Create ALL required sections upfront (Moodle 4.0+ no longer uses
        // the numsections courseformatoption — sections must be created explicitly).
        \course_create_sections_if_missing($course, range(0, $numsections));

        // Activity-count rules (per-section caps + course-wide totals). Carried from the form via
        // ajax; defaults applied when absent (e.g. an older saved session).
        $counts        = $this->countsToArray($coursedata->_counts ?? null);
        $quizcfg       = $counts['quiz'];
        $assigncfg     = $counts['assignment'];
        $h5pcfg        = $counts['h5p'];
        $quiztotalcap  = !empty($quizcfg['total_enabled'])   ? (int) $quizcfg['total_max']   : PHP_INT_MAX;
        $assigntotcap  = !empty($assigncfg['total_enabled']) ? (int) $assigncfg['total_max'] : PHP_INT_MAX;
        $h5ptotalcap   = !empty($h5pcfg['total_enabled'])    ? (int) $h5pcfg['total_max']    : PHP_INT_MAX;
        $quizmade      = 0;
        $assignmade    = 0;
        $h5pmade       = 0;

        // Populate sections and add modules.
        foreach ($coursedata->sections as $index => $section) {
            $sectionnum = $index + 1;
            $section = $this->normalizeSection($section);

            // Update section name and summary directly in DB.
            $DB->set_field(
                'course_sections',
                'name',
                $section->name,
                ['course' => $courseid, 'section' => $sectionnum]
            );
            if (!empty($section->description)) {
                $DB->set_field(
                    'course_sections',
                    'summary',
                    $section->description,
                    ['course' => $courseid, 'section' => $sectionnum]
                );
            }

            // Create lesson page.
            if (!empty($section->lesson)) {
                $this->createLessonPage($course, $sectionnum, $section);
            }

            // Create quizzes — one per item in the section's quizzes[] array, clamped to the
            // per-section cap and the course-wide total cap.
            $quizcap = $this->perSectionCap($quizcfg);
            $quizthissection = 0;
            $multiquiz = count($section->quizzes) > 1;
            foreach ($section->quizzes as $quiz) {
                if ($quizmade >= $quiztotalcap || $quizthissection >= $quizcap) {
                    break;
                }
                if (empty($quiz->questions)) {
                    continue;
                }
                $this->createQuiz(
                    $course,
                    $sectionnum,
                    $section->name ?? ('Section ' . $sectionnum),
                    $quiz,
                    $multiquiz ? ($quizthissection + 1) : 0
                );
                $quizthissection++;
                $quizmade++;
            }

            // Create assignments — one per item in the section's assignments[] array, clamped.
            $assigncap = $this->perSectionCap($assigncfg);
            $assignthissection = 0;
            foreach ($section->assignments as $assignment) {
                if ($assignmade >= $assigntotcap || $assignthissection >= $assigncap) {
                    break;
                }
                if (empty($assignment)) {
                    continue;
                }
                $this->createAssignment($course, $sectionnum, $assignment);
                $assignthissection++;
                $assignmade++;
            }

            // Create H5P activities via CourseAgent API (optional, non-fatal) — one per chosen
            // type in the section's h5p[] array, clamped. Each call is a billable SaaS request.
            if (!empty($coursedata->_include_h5p) && !empty($section->h5p)) {
                $saaskey = get_config('local_courseagent', 'saas_api_key') ?: '';
                $saasurl = \local_courseagent\saas_http::base_url();
                if (!empty($saaskey)) {
                    $allvalidtypes = [
                        'single_choice_set', 'summary', 'drag_the_words',
                        'multiple_choice', 'true_false', 'fill_in_blanks',
                        'quiz_question_set', 'dialog_cards', 'essay',
                        'mark_the_words', 'sort_the_paragraphs', 'crossword',
                        'find_the_words', 'accordion', 'personality_quiz', 'chart', 'timeline',
                    ];
                    $allowedtypes = $allvalidtypes;
                    $rawtypes = $coursedata->_h5p_types ?? '';
                    if (!empty($rawtypes)) {
                        $parsed = array_values(array_filter(
                            array_map('trim', explode(',', $rawtypes)),
                            fn($t) => in_array($t, $allvalidtypes, true)
                        ));
                        if (!empty($parsed)) {
                            $allowedtypes = $parsed;
                        }
                    }
                    $h5pcap = $this->perSectionCap($h5pcfg);
                    $h5pthissection = 0;
                    foreach ($section->h5p as $h5ptype) {
                        if ($h5pmade >= $h5ptotalcap || $h5pthissection >= $h5pcap) {
                            break;
                        }
                        $this->createH5pActivities($course, $sectionnum, $section, $saaskey, $saasurl, $allowedtypes, $h5ptype);
                        $h5pthissection++;
                        $h5pmade++;
                    }
                }
            }
        }

        // Rebuild course cache.
        \rebuild_course_cache($courseid, true);

        // Save session record.
        $session               = new \stdClass();
        $session->userid       = $USER->id;
        $session->courseid     = $courseid;
        $session->status       = 'published';
        $session->course_json  = json_encode($coursedata);
        $session->timecreated  = time();
        $session->timemodified = time();
        $DB->insert_record('courseagent_sessions', $session);

        return ['courseid' => $courseid, 'h5p_warnings' => $this->h5pwarnings];
    }

    /**
     * Create a stub course_modules row so we have a cmid BEFORE calling
     * {module}_add_instance() — Moodle's own module libs (e.g. page_add_instance)
     * expect $data->coursemodule to already exist and update it themselves.
     *
     * @param  stdClass $course
     * @param  string   $modulename  e.g. 'page', 'quiz', 'assign'
     * @return int      The new course_modules.id (cmid)
     */
    private function createCmStub($course, $modulename)
    {
        global $DB;

        $moduleid = $DB->get_field('modules', 'id', ['name' => $modulename], MUST_EXIST);

        $cm                      = new \stdClass();
        $cm->course              = $course->id;
        $cm->module              = $moduleid;
        $cm->instance            = 0;   // Updated by _add_instance().
        $cm->section             = 0;   // Moved by course_add_cm_to_section().
        $cm->visible             = 1;
        $cm->visibleold          = 1;
        $cm->visibleoncoursepage = 1;
        $cm->groupmode           = 0;
        $cm->groupingid          = 0;
        $cm->added               = time();
        $cm->id = $DB->insert_record('course_modules', $cm);

        return $cm->id;
    }

    /**
     * Move a cm to its target section after _add_instance() has set the instance id.
     */
    private function placeCmInSection($course, $cmid, $sectionnum)
    {
        \course_add_cm_to_section($course, $cmid, $sectionnum);
    }

    /**
     * Create a lesson page (mod_page).
     */
    private function createLessonPage($course, $sectionnum, $section)
    {
        global $CFG;
        require_once($CFG->dirroot . '/mod/page/lib.php');
        require_once($CFG->dirroot . '/lib/resourcelib.php');

        // Must create the CM stub first — page_add_instance() uses $data->coursemodule
        // to update course_modules.instance after inserting into mdl_page.
        $cmid = $this->createCmStub($course, 'page');

        $content = !empty($section->lesson->content_html) ? $section->lesson->content_html : '';
        if (!empty($section->lesson->key_points) && is_array($section->lesson->key_points)) {
            $content .= '<h3>Key Points</h3><ul>';
            foreach ($section->lesson->key_points as $kp) {
                $content .= '<li>' . $kp . '</li>';
            }
            $content .= '</ul>';
        }

        $moduleinfo                   = new \stdClass();
        $moduleinfo->coursemodule     = $cmid;   // Required by page_add_instance().
        $moduleinfo->course           = $course->id;
        $moduleinfo->name             = $section->name . ' - Lesson';
        $moduleinfo->intro            = !empty($section->lesson->summary) ? $section->lesson->summary : '';
        $moduleinfo->introformat      = FORMAT_HTML;
        $moduleinfo->content          = $content;
        $moduleinfo->contentformat    = FORMAT_HTML;
        $moduleinfo->trusttext        = 1;  // Prevent Moodle from stripping Mermaid/emoji HTML.
        $moduleinfo->display          = RESOURCELIB_DISPLAY_OPEN;
        $moduleinfo->printintro       = 0;
        $moduleinfo->printlastmodified = 1;
        $moduleinfo->timemodified     = time();

        \page_add_instance($moduleinfo, null);
        $this->placeCmInSection($course, $cmid, $sectionnum);
    }

    /**
     * Create a quiz (mod_quiz) and populate it with AI-generated MCQ questions.
     */
    /**
     * Create a quiz module from a single quiz object.
     *
     * @param \stdClass $course
     * @param int       $sectionnum
     * @param string    $sectionname Section name (for the quiz title)
     * @param \stdClass $quiz        One quiz object ({name, questions[]})
     * @param int       $suffix      When a section has >1 quiz, the 1-based index appended to the name (0 = none)
     */
    private function createQuiz($course, $sectionnum, $sectionname, $quiz, $suffix = 0)
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/lib/questionlib.php');
        require_once($CFG->dirroot . '/question/engine/lib.php');
        require_once($CFG->dirroot . '/question/type/multichoice/questiontype.php');

        $cmid = $this->createCmStub($course, 'quiz');

        $questions    = $quiz->questions ?? [];
        $numquestions = count($questions);
        $totalpoints  = max($numquestions, 1);

        // Generate descriptive quiz name based on section content.
        $sectionnum = (int) $sectionnum;
        $quiznametopic = !empty($sectionname) ? $sectionname : 'Section ' . $sectionnum;
        // Clean up the topic for use in the quiz name.
        $quiznametopic = preg_replace('/\s*-\s*Lesson$/i', '', $quiznametopic);
        $quiznametopic = preg_replace('/\s*-\s*Quiz$/i', '', $quiznametopic);
        $quizname = 'Section ' . $sectionnum . ': ' . $quiznametopic . ' - Knowledge Check';
        if ($suffix > 0) {
            $quizname .= ' (' . $suffix . ')';
        }

        $moduleinfo                              = new \stdClass();
        $moduleinfo->coursemodule               = $cmid;
        $moduleinfo->course                     = $course->id;
        $moduleinfo->name                       = $quizname;
        $moduleinfo->intro                      = 'Test your understanding of this section.';
        $moduleinfo->introformat                = FORMAT_HTML;
        $moduleinfo->timeopen                   = 0;
        $moduleinfo->timeclose                  = 0;
        $moduleinfo->timelimit                  = 0;
        $moduleinfo->overduehandling            = 'autoabandon';
        $moduleinfo->graceperiod                = 0;
        $moduleinfo->attempts                   = 3;
        $moduleinfo->grademethod                = 1;
        $moduleinfo->decimalpoints              = 2;
        $moduleinfo->questiondecimalpoints      = -1;
        $moduleinfo->shuffleanswers             = 1;
        $moduleinfo->sumgrades                  = (float) $totalpoints;
        $moduleinfo->grade                      = 100;
        $moduleinfo->timecreated                = time();
        $moduleinfo->timemodified               = time();
        $moduleinfo->preferredbehaviour       = 'deferredfeedback';
        $moduleinfo->browsersecurity            = '-';
        $moduleinfo->delay1                     = 0;
        $moduleinfo->delay2                     = 0;
        $moduleinfo->showuserpicture            = 0;
        $moduleinfo->showblocks                 = 0;
        $moduleinfo->completionattemptsexhausted = 0;
        $moduleinfo->completionpass             = 0;
        $moduleinfo->allowofflineattempts       = 0;
        $moduleinfo->quizpassword               = '';
        $moduleinfo->reviewattempt              = 0x11110;
        $moduleinfo->reviewcorrectness          = 0x11110;
        $moduleinfo->reviewmarks                = 0x11110;
        $moduleinfo->reviewspecificfeedback     = 0x11110;
        $moduleinfo->reviewgeneralfeedback      = 0x11110;
        $moduleinfo->reviewrightanswer          = 0x11110;
        $moduleinfo->reviewoverallfeedback      = 0x11110;

        $quizid = \quiz_add_instance($moduleinfo, null);
        $this->placeCmInSection($course, $cmid, $sectionnum);

        // ── Add AI-generated MCQ questions to the quiz ────────────────────────
        if (!empty($questions)) {
            // Get or create the course question category.
            $coursecontext = \context_course::instance($course->id);
            $categoryid    = $this->getOrCreateQuestionCategory($coursecontext, $course->fullname);

            $slot = 1;
            foreach ($questions as $q) {
                if (empty($q->question) || empty($q->options) || !is_array($q->options) || count($q->options) < 2) {
                    continue; // Skip malformed questions.
                }

                $questionid = $this->createMultichoiceQuestion(
                    $categoryid,
                    $q,
                    $coursecontext
                );

                if ($questionid) {
                    $points = !empty($q->points) ? (float) $q->points : 1.0;
                    \quiz_add_quiz_question($questionid, (object)['id' => $quizid], $slot, $points);
                    $slot++;
                }
            }

            // Recalculate quiz sumgrades based on actual questions added.
            // Use Moodle 5.x grade_calculator API (quiz_update_sumgrades is deprecated).
            \mod_quiz\quiz_settings::create($quizid)->get_grade_calculator()->recompute_quiz_sumgrades();
        }
    }

    /**
     * Get or create the default question category for a course context.
     */
    private function getOrCreateQuestionCategory($context, $coursename)
    {
        global $DB;

        $existing = $DB->get_record('question_categories', [
            'contextid' => $context->id,
            'parent'    => 0,
        ]);
        if ($existing) {
            return $existing->id;
        }

        // Create a top-level category.
        $cat              = new \stdClass();
        $cat->name        = get_string('defaultfor', 'question', $coursename);
        $cat->info        = '';
        $cat->infoformat  = FORMAT_HTML;
        $cat->contextid   = $context->id;
        $cat->parent      = 0;
        $cat->sortorder   = 999;
        $cat->stamp       = make_unique_id_code();
        return $DB->insert_record('question_categories', $cat);
    }

    /**
     * Create a multichoice question in the question bank.
     *
     * @param  int       $categoryid
     * @param  stdClass  $q  Question data from AI JSON
     * @param  context   $context
     * @return int|false  Question ID or false on failure
     */
    private function createMultichoiceQuestion($categoryid, $q, $context)
    {
        global $DB, $USER;

        $options   = array_values((array) $q->options);
        $correct   = isset($q->correct_answer) ? (int) $q->correct_answer : 0;
        $correct   = max(0, min($correct, count($options) - 1));
        $points    = !empty($q->points) ? (float) $q->points : 1.0;
        $explanation = !empty($q->explanation) ? (string) $q->explanation : '';

        try {
            // ── question_bank_entries (Moodle 5.x requirement) ───────────────
            $questionbankentry = new \stdClass();
            $questionbankentry->questioncategoryid = $categoryid;
            $questionbankentry->idnumber = null;
            $questionbankentry->ownerid = $USER->id ?? 0;
            $bankentryid = $DB->insert_record('question_bank_entries', $questionbankentry);

            // ── question base record ──────────────────────────────────────────
            $question              = new \stdClass();
            $question->category   = $categoryid;
            $question->qtype      = 'multichoice';
            $question->name       = shorten_text(strip_tags($q->question), 255);
            $question->questiontext        = '<p>' . s($q->question) . '</p>';
            $question->questiontextformat  = FORMAT_HTML;
            $question->generalfeedback     = $explanation ? '<p>' . s($explanation) . '</p>' : '';
            $question->generalfeedbackformat = FORMAT_HTML;
            $question->defaultmark  = $points;
            $question->penalty      = 0.3333333;
            $question->hidden       = 0;
            $question->timecreated  = time();
            $question->timemodified = time();
            $question->createdby    = $USER->id ?? 0;
            $question->modifiedby   = $USER->id ?? 0;
            $question->stamp        = make_unique_id_code();
            $question->version      = make_unique_id_code();
            $question->contextid    = $context->id;

            $questionid = $DB->insert_record('question', $question);

            // ── question_versions (Moodle 5.x requirement) ───────────────────
            $questionversion = new \stdClass();
            $questionversion->questionbankentryid = $bankentryid;
            $questionversion->questionid = $questionid;
            $questionversion->version = 1;
            $questionversion->status = 'ready';
            $DB->insert_record('question_versions', $questionversion);

            // ── qtype_multichoice_options ─────────────────────────────────────
            $mcoptions                      = new \stdClass();
            $mcoptions->questionid          = $questionid;
            $mcoptions->layout              = 0; // Vertical.
            $mcoptions->single              = 1; // Single correct answer.
            $mcoptions->shuffleanswers      = 1;
            $mcoptions->correctfeedback     = 'Correct!';
            $mcoptions->correctfeedbackformat      = FORMAT_HTML;
            $mcoptions->partiallycorrectfeedback   = 'Partially correct.';
            $mcoptions->partiallycorrectfeedbackformat = FORMAT_HTML;
            $mcoptions->incorrectfeedback          = 'Incorrect. The correct answer was: ' . s($options[$correct]);
            $mcoptions->incorrectfeedbackformat    = FORMAT_HTML;
            $mcoptions->answernumbering     = 'abc';
            $mcoptions->shownumcorrect      = 0;
            $DB->insert_record('qtype_multichoice_options', $mcoptions);

            // ── question_answers (one per option) ────────────────────────────
            foreach ($options as $i => $opttext) {
                $answer                  = new \stdClass();
                $answer->question        = $questionid;
                $answer->answer          = s($opttext);
                $answer->answerformat    = FORMAT_HTML;
                $answer->fraction        = ($i === $correct) ? 1.0 : 0.0;
                $answer->feedback        = ($i === $correct)
                    ? ($explanation ? s($explanation) : 'Correct!')
                    : 'Incorrect.';
                $answer->feedbackformat  = FORMAT_HTML;
                $DB->insert_record('question_answers', $answer);
            }

            return $questionid;
        } catch (\Exception $e) {
            // Log the error but don't abort the whole course creation.
            debugging('Course Agent: Failed to create question: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Escape HTML, then render inline markdown (**bold**, `code`) on the safe string.
     *
     * @param string $text
     * @return string
     */
    private function inline_markdown(string $text): string
    {
        $text = s($text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        return $text;
    }

    /**
     * Clean one AI assignment instruction into <li> inner HTML: strip its own leading
     * number, render bold/code, and turn "-" lines into a nested bullet list.
     *
     * @param string $inst
     * @return string
     */
    private function format_instruction_html(string $inst): string
    {
        $lines = explode("\n", str_replace("\r", '', $inst));
        $main = '';
        $bullets = [];
        foreach ($lines as $line) {
            if (preg_match('/^\s*[-•*]\s+/u', $line)) {
                $bullets[] = $this->inline_markdown(preg_replace('/^\s*[-•*]\s+/u', '', $line));
            } else if (trim($line) !== '') {
                // First non-bullet line is the step; strip a leading ordinal like "1. " or "2) ".
                $cleaned = ($main === '') ? preg_replace('/^\s*\d+[.)]\s+/', '', $line) : $line;
                $main .= ($main === '' ? '' : ' ') . $this->inline_markdown(trim($cleaned));
            }
        }
        $out = $main;
        if (!empty($bullets)) {
            $out .= '<ul><li>' . implode('</li><li>', $bullets) . '</li></ul>';
        }
        return $out;
    }

    /**
     * Create an assignment (mod_assign).
     */
    private function createAssignment($course, $sectionnum, $assignmentdata)
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        $cmid = $this->createCmStub($course, 'assign');

        // Debug log the incoming assignment data.
        debugging('Course Agent: Creating assignment in section ' . $sectionnum .
                  ' with data: ' . json_encode($assignmentdata), DEBUG_DEVELOPER);

        $intro = !empty($assignmentdata->description) ? $assignmentdata->description : '';
        if (!empty($assignmentdata->instructions) && is_array($assignmentdata->instructions)) {
            $intro .= '<h4>Instructions</h4><ol>';
            foreach ($assignmentdata->instructions as $inst) {
                $intro .= '<li>' . $this->format_instruction_html((string)$inst) . '</li>';
            }
            $intro .= '</ol>';
        }
        if (!empty($assignmentdata->word_count)) {
            $intro .= '<p><strong>Word count:</strong> ' . $assignmentdata->word_count . ' words.</p>';
        }

        $moduleinfo                          = new \stdClass();
        $moduleinfo->coursemodule            = $cmid;
        $moduleinfo->course                  = $course->id;
        $moduleinfo->name                    = !empty($assignmentdata->title) ? $assignmentdata->title : 'Assignment';
        $moduleinfo->intro                   = $intro;
        $moduleinfo->introformat             = FORMAT_HTML;
        $moduleinfo->alwaysshowdescription   = 1;
        $moduleinfo->submissiondrafts        = 0;
        $moduleinfo->requiresubmissionstatement = 0;
        $moduleinfo->sendnotifications       = 0;
        $moduleinfo->sendlatenotifications   = 0;
        $moduleinfo->duedate                 = 0;
        $moduleinfo->allowsubmissionsfromdate = 0;
        $moduleinfo->grade                   = 100;
        $moduleinfo->cutoffdate              = 0;
        $moduleinfo->gradingduedate          = 0;
        $moduleinfo->teamsubmission          = 0;
        $moduleinfo->requireallteammemberssubmit = 0;
        $moduleinfo->teamsubmissiongroupingid = 0;
        $moduleinfo->blindmarking            = 0;
        $moduleinfo->attemptreopenmethod     = 'none';
        $moduleinfo->maxattempts             = -1;
        $moduleinfo->markingworkflow         = 0;
        $moduleinfo->markingallocation       = 0;
        $moduleinfo->assignsubmission_onlinetext_enabled = 1;
        $moduleinfo->assignsubmission_file_enabled       = 1;
        $moduleinfo->assignsubmission_file_maxfiles      = 5;
        $moduleinfo->assignsubmission_file_maxsizebytes  = 10485760; // 10 MB

        // Add required properties that assign_add_instance expects.
        $moduleinfo->courseid   = $course->id;  // Required by Moodle 5.x assign_add_instance.
        $moduleinfo->maxbytes  = $course->maxbytes ?? 10485760;  // Course upload limit.
        $moduleinfo->section   = $sectionnum;
        $moduleinfo->visible   = 1;
        $moduleinfo->visibleoncoursepage = 1;
        $moduleinfo->cmidnumber = '';  // ID number (optional, must be set).

        try {
            $instanceid = \assign_add_instance($moduleinfo, null);
            if (!$instanceid) {
                debugging('Course Agent: assign_add_instance returned false for section ' . $sectionnum, DEBUG_DEVELOPER);
                return;
            }
            debugging('Course Agent: Assignment created with instance ID ' . $instanceid . ' in section ' . $sectionnum, DEBUG_DEVELOPER);

            // assign_add_instance does NOT update course_modules.instance.
            // (unlike page_add_instance / quiz_after_add_or_update which do).
            // Without this, the CM has instance=0 and the assignment is invisible.
            $DB->set_field('course_modules', 'instance', $instanceid, ['id' => $cmid]);

            $this->placeCmInSection($course, $cmid, $sectionnum);
        } catch (\Exception $e) {
            debugging('Course Agent: Failed to create assignment in section ' . $sectionnum .
                      ': ' . $e->getMessage());
            debugging('Course Agent: Failed to create assignment in section ' . $sectionnum .
                      ': ' . $e->getMessage() . '\n' . $e->getTraceAsString(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Call the CourseAgent API to generate an H5P activity for a section and attach it to the course.
     * Non-fatal: failures are collected in $this->h5pwarnings and logged; course publish is not aborted.
     *
     * @param \stdClass $course     Moodle course record
     * @param int       $sectionnum 1-based section number
     * @param \stdClass $section    Section data from AI (has ->lesson->content_html)
     * @param string    $saaskey    License key
     * @param string    $saasurl    SaaS base URL (no trailing slash)
     * @param string[]  $allowedtypes Types the teacher's plan permits
     * @param string|null $forcetype One specific type to generate (from the section's h5p[] list); null = auto-pick
     */
    private function createH5pActivities($course, $sectionnum, $section, $saaskey, $saasurl, $allowedtypes = [], $forcetype = null)
    {
        $content = strip_tags($section->lesson->content_html ?? $section->description ?? '');
        if (empty(trim($content))) {
            return;
        }

        // Use AI-chosen type if it's in the teacher's allowed list. Fall back to first allowed type.
        if (empty($allowedtypes)) {
            $allowedtypes = [
                'single_choice_set', 'summary', 'drag_the_words',
                'multiple_choice', 'true_false', 'fill_in_blanks',
                'quiz_question_set', 'dialog_cards', 'essay',
                'mark_the_words', 'sort_the_paragraphs', 'crossword',
                'find_the_words', 'accordion', 'personality_quiz', 'chart', 'timeline',
            ];
        }
        $activitytype = null;
        if (!empty($forcetype) && in_array($forcetype, $allowedtypes, true)) {
            // Caller specified exactly which type to create (one entry from the section's h5p[] list).
            $activitytype = $forcetype;
        } elseif (!empty($section->h5p_type) && in_array($section->h5p_type, $allowedtypes, true)) {
            $activitytype = $section->h5p_type;
        } else {
            // AI type missing or not in allowed list — rotate through allowed types.
            $activitytype = $allowedtypes[($sectionnum - 1) % count($allowedtypes)];
        }

        $payload = [
            'activity_type' => $activitytype,
            'course_id'     => (string) $course->id,
            'text'          => mb_substr($content, 0, 40000),
        ];

        require_once(__DIR__ . '/saas_http.php');
        $ch = curl_init($saasurl . '/api/v1/h5p/generate');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => \local_courseagent\saas_http::headers($saaskey),
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $responseraw = curl_exec($ch);
        $httpcode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerror   = curl_error($ch);
        curl_close($ch);

        // Handle API error codes with user-visible messages.
        if ($curlerror || $httpcode >= 500) {
            $msg = get_string('h5p_service_unavailable', 'local_courseagent');
            $this->h5pwarnings[] = $msg;
            debugging('CourseAgent H5P section ' . $sectionnum . ': service unavailable (HTTP ' . $httpcode . '): ' . $curlerror, DEBUG_DEVELOPER);
            return;
        }

        if ($httpcode === 401) {
            $this->h5pwarnings[] = get_string('h5p_invalid_key', 'local_courseagent');
            debugging('CourseAgent H5P section ' . $sectionnum . ': invalid key (401)', DEBUG_DEVELOPER);
            return;
        }

        if ($httpcode === 403) {
            $this->h5pwarnings[] = get_string('h5p_plan_restriction', 'local_courseagent');
            debugging('CourseAgent H5P section ' . $sectionnum . ': plan restriction (403)', DEBUG_DEVELOPER);
            return;
        }

        if ($httpcode === 402) {
            $this->h5pwarnings[] = get_string('h5p_no_plan', 'local_courseagent');
            debugging('CourseAgent H5P section ' . $sectionnum . ': no active plan (402)', DEBUG_DEVELOPER);
            return;
        }

        if ($httpcode === 429) {
            $errdata = json_decode($responseraw ?? '', true) ?? [];
            $errcode = $errdata['error'] ?? '';
            $msg = ($errcode === 'quota_exceeded')
                ? get_string('h5p_quota_exceeded', 'local_courseagent')
                : get_string('h5p_rate_limited', 'local_courseagent');
            $this->h5pwarnings[] = $msg;
            debugging('CourseAgent H5P section ' . $sectionnum . ': ' . $msg, DEBUG_DEVELOPER);
            return;
        }

        if ($httpcode !== 200) {
            $msg = get_string('h5p_service_unavailable', 'local_courseagent');
            $this->h5pwarnings[] = $msg;
            debugging('CourseAgent H5P section ' . $sectionnum . ': unexpected HTTP ' . $httpcode, DEBUG_DEVELOPER);
            return;
        }

        $h5pparams = json_decode($responseraw ?? '');
        if (json_last_error() !== JSON_ERROR_NONE || empty($h5pparams)) {
            debugging('CourseAgent H5P section ' . $sectionnum . ': invalid JSON from CourseAgent API', DEBUG_DEVELOPER);
            return;
        }

        $h5ppath = $this->buildH5pPackage($h5pparams, $activitytype);
        if (!$h5ppath) {
            return;
        }

        $this->createH5pModule($course, $sectionnum, $section->name ?? ('Section ' . $sectionnum), $h5ppath);

        // Clean up temp file.
        @unlink($h5ppath);
    }

    /**
     * Build a .h5p ZIP package from H5P params returned by the SaaS.
     *
     * @param \stdClass $h5pparams    H5P content params (goes into content/content.json)
     * @param string    $activitytype One of: single_choice_set, summary, drag_the_words
     * @return string|false Absolute path to the .h5p ZIP, or false on failure
     */
    private function buildH5pPackage($h5pparams, $activitytype)
    {
        $librarymap = [
            'single_choice_set' => ['machineName' => 'H5P.SingleChoiceSet', 'majorVersion' => 1, 'minorVersion' => 11],
            'summary'           => ['machineName' => 'H5P.Summary',          'majorVersion' => 1, 'minorVersion' => 10],
            'drag_the_words'    => ['machineName' => 'H5P.DragText',         'majorVersion' => 1, 'minorVersion' => 8],
            'multiple_choice'    => ['machineName' => 'H5P.MultiChoice',     'majorVersion' => 1, 'minorVersion' => 16],
            'true_false'         => ['machineName' => 'H5P.TrueFalse',       'majorVersion' => 1, 'minorVersion' => 8],
            'fill_in_blanks'     => ['machineName' => 'H5P.Blanks',          'majorVersion' => 1, 'minorVersion' => 14],
            'quiz_question_set'  => ['machineName' => 'H5P.QuestionSet',     'majorVersion' => 1, 'minorVersion' => 20],
            'dialog_cards'       => ['machineName' => 'H5P.Dialogcards',     'majorVersion' => 1, 'minorVersion' => 9],
            'essay'              => ['machineName' => 'H5P.Essay',           'majorVersion' => 1, 'minorVersion' => 5],
            'mark_the_words'     => ['machineName' => 'H5P.MarkTheWords',    'majorVersion' => 1, 'minorVersion' => 9],
            'sort_the_paragraphs' => ['machineName' => 'H5P.SortParagraphs', 'majorVersion' => 0, 'minorVersion' => 11],
            'crossword'          => ['machineName' => 'H5P.Crossword',       'majorVersion' => 0, 'minorVersion' => 5],
            'find_the_words'     => ['machineName' => 'H5P.FindTheWords',    'majorVersion' => 1, 'minorVersion' => 4],
            'accordion'          => ['machineName' => 'H5P.Accordion',       'majorVersion' => 1, 'minorVersion' => 0],
            'personality_quiz'   => ['machineName' => 'H5P.PersonalityQuiz','majorVersion' => 1, 'minorVersion' => 0],
            'chart'              => ['machineName' => 'H5P.Chart',           'majorVersion' => 1, 'minorVersion' => 2],
            'timeline'           => ['machineName' => 'H5P.Timeline',        'majorVersion' => 1, 'minorVersion' => 1],
        ];
        $lib = $librarymap[$activitytype] ?? $librarymap['single_choice_set'];

        $h5pjson = [
            'title'                 => $h5pparams->title ?? 'H5P Activity',
            'language'              => 'und',
            'mainLibrary'           => $lib['machineName'],
            'embedTypes'            => ['div'],
            'license'               => 'U',
            'preloadedDependencies' => [
                [
                    'machineName'  => $lib['machineName'],
                    'majorVersion' => $lib['majorVersion'],
                    'minorVersion' => $lib['minorVersion'],
                ],
            ],
        ];

        // Backend returns {title, h5p_library, params: {...}}; content.json needs only the inner params.
        $contentparams = isset($h5pparams->params) ? $h5pparams->params : $h5pparams;

        $tmpdir = make_temp_directory('courseagent_h5p') . '/' . uniqid('h5p_', true);
        if (!mkdir($tmpdir . '/content', 0777, true)) {
            debugging('CourseAgent H5P: could not create temp dir', DEBUG_DEVELOPER);
            return false;
        }

        file_put_contents($tmpdir . '/h5p.json', json_encode($h5pjson, JSON_PRETTY_PRINT));
        file_put_contents($tmpdir . '/content/content.json', json_encode($contentparams));

        $zippath = $tmpdir . '/activity.h5p';
        $zip = new \ZipArchive();
        if ($zip->open($zippath, \ZipArchive::CREATE) !== true) {
            debugging('CourseAgent H5P: could not create ZIP at ' . $zippath, DEBUG_DEVELOPER);
            return false;
        }
        $zip->addFile($tmpdir . '/h5p.json', 'h5p.json');
        $zip->addFile($tmpdir . '/content/content.json', 'content/content.json');
        $zip->close();

        return $zippath;
    }

    /**
     * Create a mod_h5pactivity course module and attach the .h5p package file.
     *
     * @param \stdClass $course      Moodle course record
     * @param int       $sectionnum  1-based section number
     * @param string    $activityname Name prefix for the module
     * @param string    $h5ppath     Absolute path to the .h5p ZIP file
     */
    private function createH5pModule($course, $sectionnum, $activityname, $h5ppath)
    {
        global $DB;

        if (!$DB->record_exists('modules', ['name' => 'h5pactivity'])) {
            debugging('CourseAgent H5P: mod_h5pactivity is not installed on this Moodle', DEBUG_DEVELOPER);
            return;
        }

        $cmid = $this->createCmStub($course, 'h5pactivity');

        // Insert h5pactivity record directly (avoids file_manager draft dependency).
        $record                  = new \stdClass();
        $record->course          = $course->id;
        $record->name            = $activityname . ' — H5P Activity';
        $record->timecreated     = time();
        $record->timemodified    = time();
        $record->intro           = '';
        $record->introformat     = FORMAT_HTML;
        $record->grade           = 100;
        $record->displayoptions  = 0;
        $record->enabletracking  = 1;
        $record->grademethod     = 1;
        $instanceid = $DB->insert_record('h5pactivity', $record);

        $DB->set_field('course_modules', 'instance', $instanceid, ['id' => $cmid]);

        // Store the .h5p file in Moodle's file system.
        $context    = \context_module::instance($cmid);
        $fs         = get_file_storage();
        $filerecord = [
            'contextid'    => $context->id,
            'component'    => 'mod_h5pactivity',
            'filearea'     => 'package',
            'itemid'       => 0,
            'filepath'     => '/',
            'filename'     => 'activity.h5p',
            'timecreated'  => time(),
            'timemodified' => time(),
        ];
        $fs->create_file_from_pathname($filerecord, $h5ppath);

        $this->placeCmInSection($course, $cmid, $sectionnum);
    }

    /**
     * Write progress update to temp file for client polling.
     *
     * @param int    $step    Step number (1=outline, 2=generating, 3=finalizing)
     * @param int    $percent Progress percentage 0-100
     * @param string $message Status message
     */
    private function writeProgress($step, $percent, $message)
    {
        global $USER;
        $dir = make_temp_directory('courseagent');
        $file = $dir . '/progress_' . $USER->id . '.json';
        $data = [
            'step'    => $step,
            'percent' => $percent,
            'message' => $message,
            'time'   => time(),
        ];
        file_put_contents($file, json_encode($data) . "\n", FILE_APPEND);
    }

    /**
     * Edit a specific item in the course using AI.
     *
     * @param stdClass $coursedata Course data object
     * @param string $targettype Target type: section, lesson, quiz, assignment, question
     * @param int $targetindex Section index (0-based)
     * @param int|null $questionindex Question index (for question target type)
     * @param string $userprompt User's edit request
     * @return stdClass Result with coursedata and message
     */
    public function editItem($coursedata, $targettype, $targetindex, $questionindex, $userprompt)
    {
        $sections = $coursedata->sections ?? [];
        if (!isset($sections[$targetindex])) {
            throw new \Exception('Section not found at index ' . $targetindex);
        }

        $section = $sections[$targetindex];
        $targetitem = null;
        $itemtype = '';

        // Identify target item based on targettype.
        switch ($targettype) {
            case 'lesson':
            case 'section':
                $targetitem = $section->lesson ?? null;
                $itemtype = 'lesson';
                break;
            case 'quiz':
                $targetitem = $section->quiz ?? null;
                $itemtype = 'quiz';
                break;
            case 'question':
                if (!empty($section->quiz->questions[$questionindex])) {
                    $targetitem = $section->quiz->questions[$questionindex];
                    $itemtype = 'question';
                }
                break;
            case 'assignment':
                $targetitem = $section->assignment ?? null;
                $itemtype = 'assignment';
                break;
            default:
                throw new \Exception('Unknown target type: ' . $targettype);
        }

        if (!$targetitem) {
            throw new \Exception('No ' . $itemtype . ' found in section ' . ($targetindex + 1));
        }

        // Build context for AI.
        $prompt = $this->buildEditPrompt($itemtype, $section, $targetitem, $questionindex, $userprompt);

        // System prompt for strict JSON output.
        $systemprompt = "You are a JSON generator. Return ONLY valid JSON - no markdown code blocks, no explanations, no conversational text. " .
            "The JSON must match the exact structure expected. Start with { and end with }.";

        // Triple-nested fallback: provider → model → 3 retries. Mirrors generateCourseOutline().
        $providers   = $this->buildFallbackProviders(null, null);
        $lasterror   = null;
        $fallbacklog = [];
        $response    = null;
        $usedprov    = null;
        $usedmodel   = null;

        foreach ($providers as $prov) {
            $ratelimited = false;
            foreach ($prov['models'] as $trymodel) {
                for ($retry = 1; $retry <= 3; $retry++) {
                    try {
                        $response  = provider::callApi($prompt, $prov['providerid'], $trymodel, $systemprompt);
                        $usedprov  = $prov['providername'];
                        $usedmodel = $trymodel;
                        break 3;
                    } catch (\Throwable $e) {
                        $lasterror   = $e->getMessage();
                        $isratelimit = $this->isRateLimitError($lasterror);
                        $fallbacklog[] = [
                            'provider' => $prov['providername'],
                            'model'    => $trymodel ?: '(default)',
                            'retry'    => $retry,
                            'reason'   => $isratelimit ? 'rate_limit' : 'error',
                            'message'  => $lasterror,
                        ];
                        if ($isratelimit) {
                            $ratelimited = true;
                            break 2;
                        }
                    }
                }
            }
        }

        if ($response === null) {
            throw new \Exception('All AI providers exhausted. Last error: ' . $lasterror);
        }

        // Clean response and parse.
        $response = trim($response);
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/', $response, $matches)) {
            $response = trim($matches[1]);
        }

        // Sanitize.
        $response = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $response);
        ini_set('pcre.backtrack_limit', 10000000);
        $sanitized = preg_replace_callback('/"((?:[^"\\\\]|\\\\.)*)"\/s', function ($m) {
            $inner = $m[1];
            $inner = preg_replace('/(?<!\\\\)\r/', '\\r', $inner);
            $inner = preg_replace('/(?<!\\\\)\n/', '\\n', $inner);
            $inner = preg_replace('/(?<!\\\\)\t/', '\\t', $inner);
            return '"' . $inner . '"';
        }, $response);
        if ($sanitized !== null) {
            $response = $sanitized;
        }

        $updateditem = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging('edit_item: JSON parse error: ' . json_last_error_msg());
            debugging('edit_item: response preview: ' . substr($response, 0, 1000));
            throw new \Exception('Failed to parse AI response: ' . json_last_error_msg());
        }

        // Merge updated item back into course.
        $this->mergeEditedItem($coursedata, $targettype, $targetindex, $questionindex, $updateditem);

        // Build descriptive message based on what was changed.
        $itemcount = 0;
        if ($itemtype === 'quiz') {
            $itemcount = count($updateditem->questions ?? []);
        } elseif ($itemtype === 'question') {
            $itemcount = 1;
        } elseif ($itemtype === 'lesson') {
            $itemcount = !empty($updateditem->title) ? 1 : 0;
        } elseif ($itemtype === 'assignment') {
            $itemcount = !empty($updateditem->title) ? 1 : 0;
        }
        $messagetext = "Updated {$itemtype} in section " . ($targetindex + 1);
        if ($itemtype === 'quiz' && $itemcount > 0) {
            $messagetext .= " ({$itemcount} questions)";
        }

        return (object) [
            'coursedata'    => $coursedata,
            'message'       => $messagetext,
            'fallback_log'  => $fallbacklog,
            'used_provider' => $usedprov,
            'used_model'    => $usedmodel,
        ];
    }

    /**
     * Build prompt for targeted edit.
     */
    private function buildEditPrompt($itemtype, $section, $targetitem, $questionindex, $userprompt)
    {
        $sectionname = $section->name ?? 'Section ' . ($section->index ?? 0);

        if ($itemtype === 'question') {
            $q = $targetitem;
            $qnum = ($questionindex ?? 0) + 1;
            $prompt = "You are an expert instructional designer.\n";
            $prompt .= "Edit the following quiz question based on the user's request.\n\n";
            $prompt .= "Target: Quiz Question Q{$qnum} in Section: {$sectionname}\n";
            $prompt .= "----------------------------------------\n";
            $prompt .= "Question: {$q->question}\n";
            $prompt .= "Options:\n";
            foreach ($q->options ?? [] as $oi => $opt) {
                $prompt .= chr(65 + $oi) . ". {$opt}\n";
            }
            $prompt .= "Correct Answer: " . chr(65 + ($q->correct_answer ?? 0)) . "\n";
            if (!empty($q->explanation)) {
                $prompt .= "Explanation: {$q->explanation}\n";
            }
            $prompt .= "\nUser Request: {$userprompt}\n\n";
            $prompt .= "Return ONLY the updated question as JSON with fields: question, options (array), correct_answer (0-3), explanation. ";
            $prompt .= "Keep all other options unchanged unless specifically requested.";
        } elseif ($itemtype === 'quiz') {
            $quiz = $targetitem;
            $prompt = "You are an expert instructional designer.\n";
            $prompt .= "Edit the following quiz based on the user's request.\n\n";
            $prompt .= "Target: Quiz in Section: {$sectionname}\n";
            $prompt .= "----------------------------------------\n";
            $prompt .= "Quiz Title: " . ($quiz->title ?? $sectionname) . "\n\n";
            $prompt .= "Current Questions:\n";
            foreach ($quiz->questions ?? [] as $qi => $q) {
                $qnum = $qi + 1;
                $prompt .= "Q{$qnum}: {$q->question}\n";
                foreach ($q->options ?? [] as $oi => $opt) {
                    $prompt .= "  " . chr(65 + $oi) . ". {$opt}\n";
                }
                $prompt .= "  Answer: " . chr(65 + ($q->correct_answer ?? 0)) . "\n";
                if (!empty($q->explanation)) {
                    $prompt .= "  Explanation: {$q->explanation}\n";
                }
                $prompt .= "\n";
            }
            $prompt .= "User Request: {$userprompt}\n\n";
            $prompt .= "IMPORTANT: Return ONLY valid JSON (no markdown, no explanation). ";
            $prompt .= "The JSON must be an object with a 'questions' array containing all " . count($quiz->questions ?? []) . " questions. ";
            $prompt .= "Preserve all existing questions unless the user explicitly asks to add/remove/replace specific ones. ";
            $prompt .= "If adding questions, add them to the questions array. ";
            $prompt .= "If removing, omit them from the array.";
        } elseif ($itemtype === 'lesson') {
            $lesson = $targetitem;
            $prompt = "You are an expert instructional designer.\n";
            $prompt .= "Edit the following lesson content based on the user's request.\n\n";
            $prompt .= "Target: Lesson in Section: {$sectionname}\n";
            $prompt .= "----------------------------------------\n";
            $prompt .= "Title: " . ($lesson->title ?? $sectionname) . "\n";
            if (!empty($lesson->summary)) {
                $prompt .= "Summary: {$lesson->summary}\n";
            }
            if (!empty($lesson->content_html)) {
                $prompt .= "Content: {$lesson->content_html}\n";
            }
            $prompt .= "\nUser Request: {$userprompt}\n\n";
            $prompt .= "Return ONLY the updated lesson as JSON with fields: title, summary, content_html. ";
            $prompt .= "Keep content_html as HTML with proper <h2>, <p>, <ul>, etc. tags.";
        } elseif ($itemtype === 'assignment') {
            $a = $targetitem;
            $prompt = "You are an expert instructional designer.\n";
            $prompt .= "Edit the following assignment based on the user's request.\n";
            $prompt .= "Do NOT reference any external files, datasets, CSVs, PDFs, or downloadable resources in the assignment. All tasks must be completable by the student using only their own knowledge and publicly available information.\n\n";
            $prompt .= "Target: Assignment in Section: {$sectionname}\n";
            $prompt .= "----------------------------------------\n";
            $prompt .= "Title: " . ($a->title ?? $sectionname) . "\n";
            if (!empty($a->description)) {
                $prompt .= "Description: {$a->description}\n";
            }
            if (!empty($a->instructions)) {
                $prompt .= "Instructions:\n";
                foreach ($a->instructions as $i => $inst) {
                    $prompt .= ($i + 1) . ". {$inst}\n";
                }
            }
            if (!empty($a->word_count)) {
                $prompt .= "Word Count: {$a->word_count}\n";
            }
            $prompt .= "\nUser Request: {$userprompt}\n\n";
            $prompt .= "Return ONLY the updated assignment as JSON with fields: title, description, instructions (array), word_count.";
        }

        $prompt .= "\n\nReturn ONLY valid JSON, no markdown fences or explanation.";

        return $prompt;
    }

    /**
     * Merge edited item back into course data.
     */
    private function mergeEditedItem($coursedata, $targettype, $targetindex, $questionindex, $updateditem)
    {
        $section = $coursedata->sections[$targetindex];

        switch ($targettype) {
            case 'question':
                $section->quiz->questions[$questionindex] = $updateditem;
                break;
            case 'quiz':
                $section->quiz = $updateditem;
                break;
            case 'lesson':
            case 'section':
                $section->lesson = $updateditem;
                break;
            case 'assignment':
                $section->assignment = $updateditem;
                break;
        }

        // The chat-edit flow works on the first quiz/assignment (item 0). Mirror the edited
        // singular field back into the array the preview render + publish layers read from.
        if (in_array($targettype, ['quiz', 'question'], true) && !empty($section->quiz)) {
            if (!isset($section->quizzes) || !is_array($section->quizzes) || count($section->quizzes) === 0) {
                $section->quizzes = [$section->quiz];
            } else {
                $section->quizzes[0] = $section->quiz;
            }
        }
        if ($targettype === 'assignment' && !empty($section->assignment)) {
            if (!isset($section->assignments) || !is_array($section->assignments) || count($section->assignments) === 0) {
                $section->assignments = [$section->assignment];
            } else {
                $section->assignments[0] = $section->assignment;
            }
        }
    }

    /**
     * Full course AI assist — NLP-direct, conversation-aware.
     * Single AI call determines intent + generates content. No separate intent detection.
     *
     * @param stdClass $coursedata Course data object
     * @param string $userprompt User's request
     * @return stdClass Result with coursedata, delta, message, response_type
     */
    public function aiAssist($coursedata, $userprompt)
    {
        global $SESSION;

        $history = $SESSION->courseagent_chat_history ?? [];

        // Pre-load the relevant section content based on section reference in text.
        $sectioncount    = count($coursedata->sections ?? []);
        $mentionedsecidx = $this->detectSectionReference($userprompt, $sectioncount);
        $userturn        = $this->buildContextPrompt($coursedata, $userprompt, $mentionedsecidx);
        $systemprompt    = $this->getAssistantSystemPrompt();

        // Flatten history for API call.
        $historymessages = array_map(
            fn($h) => ['role' => $h['role'], 'content' => $h['content']],
            array_slice($history, -10)
        );

        // Triple-nested fallback: provider → model → 3 retries. Mirrors generateCourseOutline().
        $providers   = $this->buildFallbackProviders(null, null);
        $lasterror   = null;
        $fallbacklog = [];
        $rawresponse = null;
        $usedprov    = null;
        $usedmodel   = null;

        foreach ($providers as $prov) {
            $ratelimited = false;
            foreach ($prov['models'] as $trymodel) {
                for ($retry = 1; $retry <= 3; $retry++) {
                    try {
                        $rawresponse = provider::callApi_with_history(
                            $historymessages,
                            $userturn,
                            $systemprompt,
                            $prov['providerid'],
                            $trymodel
                        );
                        $usedprov  = $prov['providername'];
                        $usedmodel = $trymodel;
                        break 3;
                    } catch (\Throwable $e) {
                        $lasterror   = $e->getMessage();
                        $isratelimit = $this->isRateLimitError($lasterror);
                        $fallbacklog[] = [
                            'provider' => $prov['providername'],
                            'model'    => $trymodel ?: '(default)',
                            'retry'    => $retry,
                            'reason'   => $isratelimit ? 'rate_limit' : 'error',
                            'message'  => $lasterror,
                        ];
                        if ($isratelimit) {
                            $ratelimited = true;
                            break 2;
                        }
                    }
                }
            }
        }

        if ($rawresponse === null) {
            return (object) [
                'coursedata'    => $coursedata,
                'delta'         => null,
                'message'       => 'All AI providers exhausted. Last error: ' . $lasterror,
                'response_type' => 'question',
                'plan_summary'  => null,
                'fallback_log'  => $fallbacklog,
                'used_provider' => null,
                'used_model'    => null,
            ];
        }

        $result = $this->parseJsonResponse($rawresponse);

        // Persist this turn to session history.
        $history[] = ['role' => 'user',      'content' => $userprompt,         'ts' => time()];
        $history[] = ['role' => 'assistant',  'content' => $result->message ?? '', 'ts' => time()];
        $SESSION->courseagent_chat_history = array_slice($history, -20);

        $rtype = $result->type ?? 'delta';

        // Delete ops require a plan step first. If AI skipped it, demote to plan.
        if ($rtype === 'delta' && ($result->op ?? '') === 'delete') {
            $confirmwords = ['yes', 'confirm', 'proceed', 'go ahead', 'do it', 'sure', 'ok', 'yep', 'yeah'];
            $usermsg = strtolower($userprompt ?? '');
            $isconfirm = false;
            foreach ($confirmwords as $word) {
                if (strpos($usermsg, $word) !== false) {
                    $isconfirm = true;
                    break;
                }
            }
            if (!$isconfirm) {
                $rtype = 'plan';
                if (empty($result->plan_summary)) {
                    $target = $result->target_type ?? 'item';
                    $sidx = (int)($result->section_index ?? 0) + 1;
                    $result->plan_summary = 'Delete ' . $target . ' in section ' . $sidx;
                }
            }
        }

        // question or plan — no course changes yet.
        // delete ops legitimately have data=null; don't block them.
        if ($rtype !== 'delta' || ($result->op !== 'delete' && empty($result->data))) {
            return (object) [
                'coursedata'    => $coursedata,
                'delta'         => null,
                'message'       => $result->message ?? '',
                'response_type' => $rtype,
                'plan_summary'  => $result->plan_summary ?? null,
                'fallback_log'  => $fallbacklog,
                'used_provider' => $usedprov,
                'used_model'    => $usedmodel,
            ];
        }

        // Build intent from AI response fields (AI determined these from NLP).
        $intent = (object) [
            'action'         => $result->op           ?? 'update',
            'target'         => $result->target_type  ?? 'lesson',
            'section_index'  => (int)($result->section_index ?? 0),
            'question_index' => isset($result->question_index) ? (int)$result->question_index : 0,
            'insert_before'  => isset($result->insert_before) ? (int)$result->insert_before : null,
        ];

        debugging('Course Agent AI assist: intent from AI response ' . json_encode($intent), DEBUG_DEVELOPER);

        // Handle delete — no AI data needed.
        if ($intent->action === 'delete') {
            $updated = $this->mergeDelta($coursedata, $intent, null);
        } else {
            $updated = $this->mergeDelta($coursedata, $intent, $result->data);
        }

        $updated->sections = array_values((array)$updated->sections);
        foreach ($updated->sections as $i => $section) {
            $section->index = $i;
        }

        $deltaobj = (object) [
            'op'             => $intent->action,
            'target_type'    => $intent->target,
            'section_index'  => $intent->section_index,
            'question_index' => $intent->target === 'question' ? $intent->question_index : null,
            'data'           => $intent->action === 'delete' ? null : $result->data,
        ];

        return (object) [
            'coursedata'    => $updated,
            'delta'         => $deltaobj,
            'message'       => $result->message ?? $this->buildSuccessMessage($intent),
            'response_type' => 'delta',
            'fallback_log'  => $fallbacklog,
            'used_provider' => $usedprov,
            'used_model'    => $usedmodel,
        ];
    }

    /**
     * Detect an explicit "section N" reference in user text.
     * Returns 0-based index or null if not found.
     *
     * @param string $text User prompt
     * @param int $total Total number of sections
     * @return int|null
     */
    private function detectSectionReference(string $text, int $total): ?int
    {
        if (preg_match('/\bsections?\s+(\d+)\b/i', $text, $m)) {
            $idx = (int)$m[1] - 1;
            if ($idx >= 0 && $idx < $total) {
                return $idx;
            }
        }
        // "the first/second/... section"
        $ordinals = ['first' => 0, 'second' => 1, 'third' => 2, 'fourth' => 3,
                     'fifth' => 4, 'sixth' => 5, 'seventh' => 6, 'eighth' => 7];
        foreach ($ordinals as $word => $idx) {
            if (preg_match('/\b' . $word . '\s+section\b/i', $text) && $idx < $total) {
                return $idx;
            }
        }
        return null;
    }

    /**
     * Build the user-turn prompt: course summary + relevant section full content + user request.
     *
     * @param stdClass $coursedata
     * @param string $userprompt
     * @param int|null $targetsectionidx 0-based index of section to include in full, or null
     * @return string
     */
    private function buildContextPrompt($coursedata, $userprompt, ?int $targetsectionidx): string
    {
        $sections = $coursedata->sections ?? [];
        $total    = count($sections);

        $prompt  = "=== COURSE: " . ($coursedata->title ?? 'Untitled') . " ===\n";
        $prompt .= "Total sections: {$total}\n\n";

        $prompt .= "=== COURSE STRUCTURE OVERVIEW ===\n";
        foreach ($sections as $i => $s) {
            $qcount = isset($s->quiz->questions) ? count((array)$s->quiz->questions) : 0;
            $prompt .= "Section " . ($i + 1) . ": \"" . ($s->name ?? 'Untitled') . "\"";
            $prompt .= " | lesson: yes";
            $prompt .= " | quiz: " . ($qcount > 0 ? "yes ({$qcount} questions)" : "no");
            $prompt .= " | assignment: " . (isset($s->assignment) ? "yes" : "no") . "\n";
        }
        $prompt .= "\n";

        // Include full content of mentioned section(s) or all sections if <=4.
        if ($targetsectionidx !== null) {
            $prompt .= $this->formatSectionFull($sections[$targetsectionidx], $targetsectionidx);
        } elseif ($total <= 4) {
            foreach ($sections as $i => $s) {
                $prompt .= $this->formatSectionFull($s, $i);
            }
        }

        $prompt .= "=== USER REQUEST ===\n";
        $prompt .= $userprompt . "\n";

        return $prompt;
    }

    /**
     * Format a single section's full content for the AI prompt.
     */
    private function formatSectionFull($section, int $idx): string
    {
        $num = $idx + 1;
        $out = "=== FULL CONTENT: SECTION {$num} — \"" . ($section->name ?? 'Untitled') . "\" ===\n";

        if (!empty($section->lesson)) {
            $out .= "LESSON:\n";
            $out .= "  title: " . ($section->lesson->title ?? '') . "\n";
            $out .= "  summary: " . ($section->lesson->summary ?? '') . "\n";
            $out .= "  content_html: " . ($section->lesson->content_html ?? '') . "\n\n";
        }

        if (isset($section->quiz->questions) && !empty($section->quiz->questions)) {
            $questions = array_values((array)$section->quiz->questions);
            $out .= "QUIZ (" . count($questions) . " questions):\n";
            foreach ($questions as $qi => $q) {
                $out .= "  Q" . ($qi + 1) . ": " . ($q->question ?? '') . "\n";
                $options = isset($q->options) ? array_values((array)$q->options) : [];
                $correctidx = isset($q->correct_answer) ? (int)$q->correct_answer : -1;
                foreach ($options as $oi => $opt) {
                    $correct = ($oi === $correctidx) ? ' [CORRECT]' : '';
                    $out .= "    " . chr(65 + $oi) . ". {$opt}{$correct}\n";
                }
                if (!empty($q->explanation)) {
                    $out .= "    Explanation: " . $q->explanation . "\n";
                }
            }
            $out .= "\n";
        }

        if (!empty($section->assignment)) {
            $a = $section->assignment;
            $out .= "ASSIGNMENT:\n";
            $out .= "  title: " . ($a->title ?? '') . "\n";
            $out .= "  description: " . ($a->description ?? '') . "\n";
            if (!empty($a->instructions)) {
                $out .= "  instructions: " . implode(" | ", (array)$a->instructions) . "\n";
            }
            if (!empty($a->word_count)) {
                $out .= "  word_count: " . $a->word_count . "\n";
            }
            $out .= "\n";
        }

        return $out;
    }

    /**
     * Static system prompt for the conversational assistant.
     * Tells AI the response schema + exact JSON schemas per target type.
     */
    private function getAssistantSystemPrompt(): string
    {
        return <<<'SYSTEMPROMPT'
You are an AI assistant for editing Moodle LMS courses. You receive a course structure, optional full section content, conversation history, and the user's request.

ALWAYS respond with ONLY a valid JSON object — no markdown fences, no extra text:
{
  "type": "question|plan|delta",
  "message": "What you say to the user (friendly, concise)",
  "plan_summary": null,
  "target_type": "lesson|quiz|question|assignment|section",
  "section_index": 0,
  "question_index": null,
  "insert_before": null,
  "op": "add|update|delete|replace",
  "data": null
}

RULES:
- type=question: Request is ambiguous or missing required info. Ask ONE focused question. Set data=null.
- type=plan: You have ALL information needed to act, but change is large/risky (rewrite full lesson, delete a section). State exactly what you will do in message. Set plan_summary to one sentence summary. Set data=null. NEVER ask the user questions inside a plan message — if you need more info first, use type=question instead.
- type=delta: Change is clear and specific, OR user said "yes"/"confirm"/"proceed" after a plan. Include data.
- section_index is ALWAYS 0-based (section 1 = index 0, section 2 = index 1, etc.).
- Parse section references from user text ("section 2", "the second section") — override any assumed default.
- question_index is 0-based. Only set for target_type=question.
- op values: add | update | delete | replace
- insert_before: for op=add, target=section ONLY — 0-based index to insert the new section BEFORE that position. null means append at end. Example: "add before section 2" → insert_before=1.

EXACT DATA SCHEMAS — data field must match these exactly:

target_type=lesson:
{"title":"","summary":"","content_html":"<h2>...</h2><p>...</p>"}

target_type=quiz (return ALL questions, existing + new):
{"name":"Section Quiz","questions":[{"question":"?","options":["A","B","C","D"],"correct_answer":0,"explanation":"","points":1}]}

target_type=question (single question only):
{"question":"?","options":["A","B","C","D"],"correct_answer":0,"explanation":"","points":1}

target_type=assignment:
{"title":"","description":"","instructions":["step 1","step 2"],"word_count":500}

target_type=section (complete new section):
{"name":"","description":"","lesson":{"title":"","summary":"","content_html":""},"quiz":{"name":"Quiz","questions":[...]},"assignment":{"title":"","description":"","instructions":[],"word_count":500}}

IMPORTANT: For "add N more questions" requests: set target_type=quiz, op=update, and include ALL existing questions PLUS the N new ones in data.questions. Never lose existing questions.
IMPORTANT: For op=delete, always set data=null — never include section/activity content in data.
IMPORTANT: Never combine asking questions with type=plan. If you lack any required detail (title, description, word count, etc.), always use type=question to gather it first, then use type=plan or type=delta once you have what you need.
MANDATORY DELETE RULE: For ANY delete operation, ALWAYS use type=plan first — even if the request is perfectly clear. In message, describe exactly what will be removed and ask the user to confirm. Only use type=delta with op=delete when the user's CURRENT message is an explicit confirmation ("yes", "proceed", "confirm", "go ahead", "do it") of a delete you described in your immediately previous response.
IMPORTANT: For assignment content — NEVER reference external files, datasets, CSVs, PDFs, or downloadable resources that students would need. All assignment tasks must be completable using only the student's own knowledge and publicly available information.
SYSTEMPROMPT;
    }

    /**
     * Extract the first balanced JSON object from a string using brace counting.
     * Avoids regex confusion caused by inner markdown fences inside content_html.
     */
    private function extractJsonObject(string $text): string
    {
        $start = strpos($text, '{');
        if ($start === false) {
            return $text;
        }
        $depth = 0;
        $instr = false;
        $esc   = false;
        $len   = strlen($text);
        for ($i = $start; $i < $len; $i++) {
            $c = $text[$i];
            if ($esc) {
                $esc = false;
                continue;
            }
            if ($c === '\\' && $instr) {
                $esc = true;
                continue;
            }
            if ($c === '"') {
                $instr = !$instr;
                continue;
            }
            if (!$instr) {
                if ($c === '{') {
                    $depth++;
                } elseif ($c === '}' && --$depth === 0) {
                    return substr($text, $start, $i - $start + 1);
                }
            }
        }
        return $text;
    }

    /**
     * Escape bare control characters inside JSON string values.
     * Character-by-character walk — immune to PCRE backtrack limits.
     */
    private function sanitizeJsonStrings(string $json): string
    {
        $out   = '';
        $len   = strlen($json);
        $instr = false;
        $esc   = false;
        for ($i = 0; $i < $len; $i++) {
            $c   = $json[$i];
            $ord = ord($c);
            if ($esc) {
                $out .= $c;
                $esc  = false;
                continue;
            }
            if ($c === '\\' && $instr) {
                $out .= $c;
                $esc  = true;
                continue;
            }
            if ($c === '"') {
                $instr = !$instr;
                $out  .= $c;
                continue;
            }
            if ($instr) {
                if ($c === "\n") {
                    $out .= '\\n';
                    continue;
                }
                if ($c === "\r") {
                    $out .= '\\r';
                    continue;
                }
                if ($c === "\t") {
                    $out .= '\\t';
                    continue;
                }
                if ($ord < 0x20) {
                    $out .= sprintf('\\u%04x', $ord);
                    continue;
                }
            } else {
                if ($ord < 0x20 && $c !== "\n" && $c !== "\r" && $c !== "\t") {
                    continue;
                }
            }
            $out .= $c;
        }
        return $out;
    }

    /**
     * Parse and sanitize AI JSON response.
     */
    private function parseJsonResponse(string $rawresponse): \stdClass
    {
        $response = trim($rawresponse);

        // Strip anchored markdown fence only.
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/s', $response, $matches)) {
            $response = trim($matches[1]);
        }

        // Extract first balanced JSON object (brace-counting, handles nested fences in content_html).
        $response = $this->extractJsonObject($response);

        // Fast path — works for clean JSON (JSON mode, no unescaped chars).
        $result = json_decode($response);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $result;
        }

        // Sanitize literal newlines/tabs/control-chars inside JSON string values.
        $sanitized = $this->sanitizeJsonStrings($response);
        $result    = json_decode($sanitized);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $result;
        }

        // Last resort: ignore invalid UTF-8 sequences.
        $result = json_decode($sanitized, false, 512, JSON_INVALID_UTF8_IGNORE);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $result;
        }

        debugging('ai_assist: JSON parse error: ' . json_last_error_msg(), DEBUG_DEVELOPER);
        debugging('ai_assist: raw response (first 5000): ' . substr($rawresponse, 0, 5000), DEBUG_DEVELOPER);
        throw new \Exception('Failed to parse AI response: ' . json_last_error_msg());
    }

    /**
     * Merge delta into course data.
     */
    private function mergeDelta($coursedata, $intent, $resultdata)
    {
        $action = $intent->action ?? 'update';
        $target = $intent->target ?? 'lesson';
        $sectionidx = $intent->section_index ?? 0;

        // Clone to avoid mutating original.
        $updated = clone $coursedata;
        if (!isset($updated->sections)) {
            $updated->sections = [];
        }

        if ($action === 'delete' && $target === 'section') {
            // Remove entire section.
            if (isset($updated->sections[$sectionidx])) {
                array_splice($updated->sections, $sectionidx, 1);
            }
        } elseif ($action === 'add' && $target === 'section') {
            // Add new section.
            $newsection = is_object($resultdata) ? $resultdata : (object) ['name' => 'New Section'];
            if (!isset($newsection->lesson)) {
                $newsection->lesson = (object) ['title' => 'Introduction', 'summary' => '', 'content_html' => ''];
            }
            if (!isset($newsection->quiz)) {
                $newsection->quiz = (object) ['name' => 'Quiz', 'questions' => []];
            }
            if (!isset($newsection->assignment)) {
                $newsection->assignment = null;
            }
            $insertbefore = $intent->insert_before ?? null;
            if ($insertbefore !== null && $insertbefore >= 0 && $insertbefore <= count($updated->sections)) {
                array_splice($updated->sections, $insertbefore, 0, [$newsection]);
            } else {
                $updated->sections[] = $newsection;
            }
        } elseif (isset($updated->sections[$sectionidx])) {
            // Update existing section.
            $section = $updated->sections[$sectionidx];

            if ($target === 'lesson' || $target === 'section') {
                $section->lesson = is_object($resultdata) ? $resultdata : ($section->lesson ?? (object) []);
            } elseif ($target === 'quiz') {
                if ($action === 'delete') {
                    $section->quiz = (object) ['name' => 'Quiz', 'questions' => []];
                } else {
                    $section->quiz = is_object($resultdata) ? $resultdata : ($section->quiz ?? (object) []);
                }
            } elseif ($target === 'question') {
                $qidx = $intent->question_index ?? 0;
                if ($action === 'delete') {
                    // Remove question.
                    if (isset($section->quiz->questions[$qidx])) {
                        array_splice($section->quiz->questions, $qidx, 1);
                    }
                } else {
                    if (!isset($section->quiz)) {
                        $section->quiz = (object) ['name' => 'Quiz', 'questions' => []];
                    }
                    if ($action === 'add') {
                        // Append new question — don't overwrite existing.
                        $section->quiz->questions[] = $resultdata;
                    } else {
                        $section->quiz->questions[$qidx] = $resultdata;
                    }
                }
            } elseif ($target === 'assignment') {
                if ($action === 'delete') {
                    $section->assignment = null;
                } else {
                    $section->assignment = is_object($resultdata) ? $resultdata : ($section->assignment ?? null);
                }
            }

            $updated->sections[$sectionidx] = $section;
        }

        return $updated;
    }

    /**
     * Build success message.
     */
    private function buildSuccessMessage($intent)
    {
        $action = $intent->action ?? 'Updated';
        $target = $intent->target ?? 'item';
        $sectionidx = ($intent->section_index ?? 0) + 1;

        $messages = [
            'add_section'    => "Added new section #{$sectionidx}.",
            'add_quiz'       => "Added quiz to section {$sectionidx}.",
            'add_question'   => "Added question to quiz in section {$sectionidx}.",
            'add_assignment' => "Added assignment to section {$sectionidx}.",
            'update_lesson'  => "Updated lesson in section {$sectionidx}.",
            'update_quiz'    => "Updated quiz in section {$sectionidx}.",
            'update_question' => "Updated question in section {$sectionidx}.",
            'update_assignment' => "Updated assignment in section {$sectionidx}.",
            'delete_question' => "Deleted question from section {$sectionidx}.",
            'delete_section'  => "Deleted section {$sectionidx}.",
        ];

        $key = $action . '_' . $target;
        return $messages[$key] ?? ucfirst($action) . ' ' . $target . ' in section ' . $sectionidx . '.';
    }
}
