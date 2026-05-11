Files found: 20
local\courseagent\ajax.php - 24 error(s) and 1 warning(s)
local\courseagent\classes\api.php - 40 error(s) and 39 warning(s)
local\courseagent\classes\extractor.php - 1 error(s) and 5 warning(s)
local\courseagent\classes\form\provider_form.php - 25 error(s) and 7 warning(s)
local\courseagent\classes\hook\navigation.php - 1 error(s) and 0 warning(s)
local\courseagent\classes\privacy\provider.php - 3 error(s) and 1 warning(s)
local\courseagent\classes\provider.php - 17 error(s) and 6 warning(s)
local\courseagent\db\access.php - 2 error(s) and 1 warning(s)
local\courseagent\db\hooks.php - 1 error(s) and 0 warning(s)
local\courseagent\db\install.php - 0 error(s) and 1 warning(s)
local\courseagent\db\uninstall.php - 0 error(s) and 1 warning(s)
local\courseagent\db\upgrade.php - 1 error(s) and 2 warning(s)
local\courseagent\index.php - 109 error(s) and 92 warning(s)
local\courseagent\lang\en\local_courseagent.php - 2 error(s) and 1 warning(s)
local\courseagent\lib.php - 3 error(s) and 2 warning(s)
local\courseagent\mycourses.php - 15 error(s) and 1 warning(s)
local\courseagent\preview.php - 21 error(s) and 39 warning(s)
local\courseagent\providers.php - 7 error(s) and 35 warning(s)
local\courseagent\settings.php - 1 error(s) and 0 warning(s)
local\courseagent\version.php - 2 error(s) and 1 warning(s)
Total: 275 error(s) and 235 warning(s)
local\courseagent\ajax.php
#1: <?php
No one-line description found in phpdocs for docblock of file ajax.php (moodle.Commenting.DocblockDescription.Missing)
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
#76: ············$course_data·=·$api->generate_course_outline(
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#91: ············$SESSION->courseagent_preview·=·$course_data;
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#97: ················'data'··········=>·$course_data,
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#98: ················'used_provider'·=>·$course_data->_used_provider_name·??·null,
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#99: ················'used_model'····=>·$course_data->_used_model·??·null,
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#100: ················'fallback_log'··=>·$course_data->_fallback_log·??·[],
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#106: ············$json_data·=·file_get_contents('php://input');
Variable "json_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#107: ············$course_data·=·json_decode($json_data);
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
Variable "json_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#114: ············$course_id·=·$api->publish_course($course_data);
Variable "course_id" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#115: ············$course_url·=·new·moodle_url('/course/view.php',·['id'·=>·$course_id]);
Variable "course_url" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
Variable "course_id" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#119: ················'course_id'·=>·$course_id,
Variable "course_id" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#120: ················'course_url'·=>·$course_url->out(false)
Variable "course_url" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#136: ················'debug'·=>·$result->debug·??·null
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#156: ················'debug'·=>·$result->debug·??·null
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#196: ················'models'·=>·$models
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#226: ················'charcount'=>·mb_strlen($text),
Expected 1 space before "=>"; 0 found (Squiz.WhiteSpace.OperatorSpacing.NoSpaceBefore)
#239: ········'error'·=>·$e->getMessage()
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#241: }
File must end with a newline character (Generic.Files.EndFileNewline.NotFound)
local\courseagent\classes\api.php
#1: <?php
No one-line description found in phpdocs for docblock of file api.php (moodle.Commenting.DocblockDescription.Missing)
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
#60: ········$fallbacklog·=·[];··//·Each·entry:·['provider'·=>·name,·'model'·=>·id,·'reason'·=>·string]
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#72: ················if·(preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/s',·$rawresponse,·$matches))·{
The use of backticks in strings is not recommended (moodle.Strings.ForbiddenStrings.Found)
#77: ················error_log('[CourseAgent]·Raw·AI·response·before·sanitize·(attempt·'·.·$attemptindex·.·'):·'·.·substr($rawresponse,·0,·3000));
The use of function error_log() is forbidden; use debugging() instead (moodle.PHP.ForbiddenFunctions.FoundWithAlternative)
Line exceeds 132 characters; contains 141 characters (moodle.Files.LineLength.TooLong)
#98: ················error_log('[CourseAgent]·Sanitized·AI·response·before·json_decode·(attempt·'·.·$attemptindex·.·'):·'·.·substr($response,·0,·3000));
The use of function error_log() is forbidden; use debugging() instead (moodle.PHP.ForbiddenFunctions.FoundWithAlternative)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
#100: ················$course_data·=·json_decode($response);
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#103: ····················$course_data·=·json_decode($response,·false,·512,·JSON_INVALID_UTF8_IGNORE);
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#106: ························error_log('[CourseAgent]·JSON·parse·FAILED·(attempt·'·.·$attemptindex·.·').·Error:·'·.·json_last_error_msg()·.·'·|·Preview:·'·.·$debugpreview);
The use of function error_log() is forbidden; use debugging() instead (moodle.PHP.ForbiddenFunctions.FoundWithAlternative)
Line exceeds 132 characters; contains 167 characters (moodle.Files.LineLength.TooLong)
#114: ················if·(empty($course_data->title)·||·empty($course_data->sections))·{
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#117: ················$returnedsectioncount·=·count($course_data->sections);
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#126: ················$course_data->_used_provider_id···=·$attempt['providerid'];
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#127: ················$course_data->_used_provider_name·=·$attempt['providername'];
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#128: ················$course_data->_used_model·········=·$attempt['model'];
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#129: ················$course_data->_fallback_log········=·$fallbacklog;
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#131: ················return·$course_data;
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#167: ········$allproviders··=·provider::get_all(true);·//·enabled·only,·sorted
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#276: ········$quizcount····=·min($maxquiz,·5);·//·default·5·questions·per·section
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#281: ········$prompt··=·"You·are·a·senior·instructional·designer·with·expertise·in·creating·comprehensive,·university-level·online·courses.\n";
Line exceeds 132 characters; contains 138 characters (moodle.Files.LineLength.TooLong)
#297: ········$prompt·.=·"The·content_html·field·MUST·contain·well-structured·HTML·with·<h2>,·<h3>,·<p>,·<ul>,·<ol>,·<strong>,·<em>,·<blockquote>,·and·<pre><code>·tags·as·appropriate.\n";
Line exceeds maximum limit of 180 characters; contains 181 characters (moodle.Files.LineLength.MaxExceeded)
#298: ········$prompt·.=·"Each·lesson·MUST·be·at·minimum·800·words·—·comprehensive·enough·for·a·student·to·learn·the·topic·without·any·other·resources.\n\n";
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
#305: ············$prompt·.=·"Examples:·📚·for·learning,·💡·for·tips,·🎯·for·objectives,·⚠️·for·warnings,·✅·for·checklists,·🔍·for·examples.\n\n";
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
#313: ············$prompt·.=·"Create·simple·diagrams·like:·flowcharts,·process·diagrams,·concept·maps,·comparison·charts,·or·illustrative·icons.\n";
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
#334: ········$prompt·.=·"The·'sections'·array·in·your·JSON·response·MUST·contain·precisely·{$numsections}·section·objects·—·no·more,·no·less.\n";
Line exceeds 132 characters; contains 140 characters (moodle.Files.LineLength.TooLong)
#341: ········$prompt·.=·'··"summary":·"A·rich·2-3·sentence·course·description·explaining·what·students·will·learn·and·why·it·matters",'·.·"\n";
Line exceeds 132 characters; contains 138 characters (moodle.Files.LineLength.TooLong)
#348: ········$prompt·.=·'········"content_html":·"<h2>Introduction</h2><p>...</p><h2>Core·Concept·1</h2><p>...</p><h3>Example</h3><p>...</p><h2>Core·Concept·2</h2><p>...</p><h2>Key·Takeaways</h2><ul><li>...</li></ul><h2>Further·Reading</h2><p>...</p>"'·.·"\n";
Line exceeds maximum limit of 180 characters; contains 255 characters (moodle.Files.LineLength.MaxExceeded)
#368: ············$prompt·.=·'········"title":·"Descriptive·assignment·title·(e.g.,·\'Research·and·Analysis·Essay\'·or·\'Code·Implementation·Task\')",'·.·"\n";
Line exceeds 132 characters; contains 153 characters (moodle.Files.LineLength.TooLong)
#369: ············$prompt·.=·'········"description":·"2-3·sentence·description·of·what·the·student·needs·to·do·and·the·learning·objective",'·.·"\n";
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
#370: ············$prompt·.=·'········"instructions":·["Clear·step·1·the·student·should·follow",·"Step·2·with·specific·requirements",·"Step·3·with·submission·guidelines"],'·.·"\n";
Line exceeds 132 characters; contains 174 characters (moodle.Files.LineLength.TooLong)
#393: ····public·function·publish_course($course_data)·{
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#397: ········if·(empty($course_data->title))·{
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#400: ········if·(empty($course_data->sections)·||·!is_array($course_data->sections))·{
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#404: ········$coursename··=·$course_data->title;
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#405: ········$numsections·=·count($course_data->sections);
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#418: ············'summary'·······=>·!empty($course_data->summary)·?·$course_data->summary·:·'',
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#431: ········foreach·($course_data->sections·as·$index·=>·$section)·{
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#471: ········$session->course_json··=·json_encode($course_data);
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#496: ········$cm->instance············=·0;···//·updated·by·_add_instance()
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#497: ········$cm->section·············=·0;···//·moved·by·course_add_cm_to_section()
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#538: ········$moduleinfo->coursemodule·····=·$cmid;···//·required·by·page_add_instance()
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#620: ········//·──·Add·AI-generated·MCQ·questions·to·the·quiz·─────────────────────
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
#629: ····················continue;·//·skip·malformed·questions
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#695: ············//·──·question_bank_entries·(Moodle·5.x·requirement)·───────────────
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
#702: ············//·──·question·base·record·────────────────────────────────────────
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
#724: ············//·──·question_versions·(Moodle·5.x·requirement)·───────────────────
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
#732: ············//·──·qtype_multichoice_options·────────────────────────────────────
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
#735: ············$mcoptions->layout··············=·0;·//·vertical
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#736: ············$mcoptions->single··············=·1;·//·single·correct·answer
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#748: ············//·──·question_answers·(one·per·option)·───────────────────────────
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
#774: ····private·function·create_assignment($course,·$sectionnum,·$assignment_data)·{
Variable "assignment_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#782: ··················'·with·data:·'·.·json_encode($assignment_data),·DEBUG_DEVELOPER);
Variable "assignment_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#784: ········$intro·=·!empty($assignment_data->description)·?·$assignment_data->description·:·'';
Variable "assignment_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
Variable "assignment_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#785: ········if·(!empty($assignment_data->instructions)·&&·is_array($assignment_data->instructions))·{
Variable "assignment_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
Variable "assignment_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#787: ············foreach·($assignment_data->instructions·as·$inst)·{
Variable "assignment_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#792: ········if·(!empty($assignment_data->word_count))·{
Variable "assignment_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#793: ············$intro·.=·'<p><strong>Word·count:</strong>·'·.·$assignment_data->word_count·.·'·words.</p>';
Variable "assignment_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#799: ········$moduleinfo->name····················=·!empty($assignment_data->title)·?·$assignment_data->title·:·'Assignment';
Variable "assignment_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
Variable "assignment_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#839: ············debugging('Course·Agent:·Assignment·created·with·instance·ID·'·.·$instanceid·.·'·in·section·'·.·$sectionnum,·DEBUG_DEVELOPER);
Line exceeds 132 characters; contains 138 characters (moodle.Files.LineLength.TooLong)
#841: ············//·assign_add_instance·does·NOT·update·course_modules.instance
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
#848: ············error_log('Course·Agent:·Failed·to·create·assignment·in·section·'·.·$sectionnum·.
The use of function error_log() is forbidden; use debugging() instead (moodle.PHP.ForbiddenFunctions.FoundWithAlternative)
#874: }
File must end with a newline character (Generic.Files.EndFileNewline.NotFound)
local\courseagent\classes\extractor.php
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
#15: defined('MOODLE_INTERNAL')·||·die();
Unexpected MOODLE_INTERNAL check. No side effects or multiple artifacts detected. (moodle.Files.MoodleInternal.MoodleInternalNotNeeded)
#71: ················throw·new·\Exception('Unsupported·file·type·".'·.·$ext·.·'".·Accepted:·TXT,·PDF,·DOCX,·PPTX,·ODT,·RTF,·MD,·CSV,·EPUB.');
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
#80: ············throw·new·\Exception('Could·not·extract·any·readable·text·from·"'·.·htmlspecialchars($filename)·.·'".·The·file·may·be·empty,·image-based,·or·DRM-protected.');
Line exceeds 132 characters; contains 170 characters (moodle.Files.LineLength.TooLong)
#94: ····//·-------------------------------------------------------------------------
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
local\courseagent\classes\form\provider_form.php
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
#19: ····public·function·definition()·{
Missing docblock for function definition (moodle.Commenting.MissingDocblock.Function)
#33: ········$mform->addElement('password',·'apikey',·get_string('provider_apikey',·'local_courseagent'),·['autocomplete'·=>·'new-password']);
Line exceeds 132 characters; contains 137 characters (moodle.Files.LineLength.TooLong)
#48: ········$mform->addElement('text',·'baseurl',·get_string('provider_baseurl',·'local_courseagent'),·['placeholder'·=>·'https://api.openai.com/v1']);
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
#54: ········$mform->addElement('text',·'endpoint',·get_string('provider_endpoint',·'local_courseagent'),·['placeholder'·=>·'chat/completions']);
Line exceeds 132 characters; contains 140 characters (moodle.Files.LineLength.TooLong)
#104: ········$this->add_action_buttons(true,·$isediting·?·get_string('savechanges',·'admin')·:·get_string('provider_add',·'local_courseagent'));
Line exceeds 132 characters; contains 139 characters (moodle.Files.LineLength.TooLong)
#122: ········$existingJson·=·htmlspecialchars(json_encode($existing),·ENT_QUOTES,·'UTF-8');
Variable "existingJson" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#124: ········$addLabel·=·get_string('provider_model_add',·'local_courseagent');
Variable "addLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#125: ········$removeLabel·=·get_string('provider_model_remove',·'local_courseagent');
Variable "removeLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#128: <div·id="courseagent-models-widget"·class="courseagent-models-widget"·data-existing="{$existingJson}">
Variable "existingJson" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#139: ················<i·class="fa·fa-plus·mr-1"></i>{$addLabel}
Variable "addLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#255: ············removeBtn.title·=·'{$removeLabel}';
Variable "removeLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#352: ········$testLabel·=·get_string('provider_test_connection',·'local_courseagent');
Variable "testLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#353: ········$loadingLabel·=·get_string('provider_test_loading',·'local_courseagent');
Variable "loadingLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#354: ········$noApikeyLabel·=·get_string('provider_test_noapikey',·'local_courseagent');
Variable "noApikeyLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#355: ········$noBaseurlLabel·=·get_string('provider_test_nobaseurl',·'local_courseagent');
Variable "noBaseurlLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#356: ········$showResponseLabel·=·get_string('provider_test_show_response',·'local_courseagent');
Variable "showResponseLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#357: ········$hideResponseLabel·=·get_string('provider_test_hide_response',·'local_courseagent');
Variable "hideResponseLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#358: ········$responseBodyLabel·=·get_string('provider_test_response_body',·'local_courseagent');
Variable "responseBodyLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#414: ········status.textContent·=·'{$noBaseurlLabel}';
Variable "noBaseurlLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#426: ········status.textContent·=·'{$noApikeyLabel}';
Variable "noApikeyLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#433: ····status.textContent·=·'{$loadingLabel}';
Variable "loadingLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#507: ············console.log('[Course·Agent]·Response·Raw:',·data.debug.response_raw·?·data.debug.response_raw.substring(0,·500)·+·'...'·:·'(none)');
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
#528: ················'<i·class="fa·fa-chevron-right·mr-1"></i>{$showResponseLabel}</span>'·+
Variable "showResponseLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#530: ················'<strong>{$responseBodyLabel}:</strong><br>'·+·escapeHtml(responseJson)·+·'</div></div>';
Variable "responseBodyLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#536: ············result.innerHTML·=·'<strong>✓·{$testLabel}:</strong>·'·+
Variable "testLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#544: ············result.innerHTML·=·'<strong>✗·{$testLabel}:</strong>·'·+
Variable "testLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#560: ····················this.innerHTML·=·'<i·class="fa·fa-chevron-down·mr-1"></i>{$hideResponseLabel}';
Variable "hideResponseLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#564: ····················this.innerHTML·=·'<i·class="fa·fa-chevron-right·mr-1"></i>{$showResponseLabel}';
Variable "showResponseLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#597: ········<i·class="fa·fa-plug·mr-1"></i>{$testLabel}
Variable "testLabel" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#665: ········//·(The·AI·call·will·fall·back·to·a·provider-level·default·if·no·model·is·specified.)
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
local\courseagent\classes\hook\navigation.php
#55: }
File must end with a newline character (Generic.Files.EndFileNewline.NotFound)
local\courseagent\classes\privacy\provider.php
#1: <?php
No one-line description found in phpdocs for docblock of file provider.php (moodle.Commenting.DocblockDescription.Missing)
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
#23: {
Opening brace should be on the same line as the declaration for class provider (Generic.Classes.OpeningBraceSameLine.BraceOnNewLine)
local\courseagent\classes\provider.php
#19: defined('MOODLE_INTERNAL')·||·die();
Unexpected MOODLE_INTERNAL check. No side effects or multiple artifacts detected. (moodle.Files.MoodleInternal.MoodleInternalNotNeeded)
#171: ········//·Debug:·Check·if·we·have·data
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#286: ····public·static·function·test_connection_raw(string·$baseurl,·string·$endpoint,·string·$apikey,·?string·$model·=·null,·string·$apiformat·=·'openai'):·\stdClass·{
Line exceeds 132 characters; contains 163 characters (moodle.Files.LineLength.TooLong)
#301: ········$start_time·=·microtime(true);
Variable "start_time" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#323: ························['parts'·=>·[['text'·=>·$testprompt]]]
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#326: ························'maxOutputTokens'·=>·50
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#327: ····················]
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#336: ························['role'·=>·'user',·'content'·=>·$testprompt]
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#498: ············'request_duration_ms'·=>·round((microtime(true)·-·$start_time)·*·1000,·2),
Variable "start_time" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#526: ········$start_time·=·microtime(true);
Variable "start_time" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#545: ························['parts'·=>·[['text'·=>·$testprompt]]]
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#548: ························'maxOutputTokens'·=>·50
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#549: ····················]
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#558: ························['role'·=>·'user',·'content'·=>·$testprompt]
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#750: ····················['parts'·=>·[['text'·=>·$prompt]]]
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#753: ····················'maxOutputTokens'·=>·8192
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#754: ················]
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#771: ····················['role'·=>·'user',·'content'·=>·$prompt]
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#818: ····private·static·function·analyzeResponseStructure($response):·array·{
Private method name "provider::analyzeResponseStructure" must be in lower-case letters only (moodle.NamingConventions.ValidFunctionName.LowercaseMethod)
#828: ············//·Check·for·Gemini·structure
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#846: ············//·Check·for·OpenAI·structure
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#858: ············//·Check·for·error·structure
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#866: }
File must end with a newline character (Generic.Files.EndFileNewline.NotFound)
local\courseagent\db\access.php
#1: <?php
No one-line description found in phpdocs for docblock of file access.php (moodle.Commenting.DocblockDescription.Missing)
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
local\courseagent\db\hooks.php
#33: ];
File must end with a newline character (Generic.Files.EndFileNewline.NotFound)
local\courseagent\db\install.php
#29: defined('MOODLE_INTERNAL')·||·die();
Unexpected MOODLE_INTERNAL check. No side effects or multiple artifacts detected. (moodle.Files.MoodleInternal.MoodleInternalNotNeeded)
local\courseagent\db\uninstall.php
#32: defined('MOODLE_INTERNAL')·||·die();
Unexpected MOODLE_INTERNAL check. No side effects or multiple artifacts detected. (moodle.Files.MoodleInternal.MoodleInternalNotNeeded)
local\courseagent\db\upgrade.php
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
#12: defined('MOODLE_INTERNAL')·||·die();
Unexpected MOODLE_INTERNAL check. No side effects or multiple artifacts detected. (moodle.Files.MoodleInternal.MoodleInternalNotNeeded)
local\courseagent\index.php
#1: <?php
No one-line description found in phpdocs for docblock of file index.php (moodle.Commenting.DocblockDescription.Missing)
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
#64: ····'enableAssignments'=>·(bool)·$enableassignments,
Expected 1 space before "=>"; 0 found (Squiz.WhiteSpace.OperatorSpacing.NoSpaceBefore)
#66: ····'defaultProviderId'=>·$defaultprovider·?·$defaultprovider->id·:·0,
Expected 1 space before "=>"; 0 found (Squiz.WhiteSpace.OperatorSpacing.NoSpaceBefore)
#96: ································<?php·print_string('coursetopic',·'local_courseagent');·?>·<span·class="text-danger">*</span>
Missing docblock for file index.php (moodle.Commenting.MissingDocblock.File)
#99: ······································placeholder="Describe·the·main·topics,·learning·objectives,·or·paste·an·existing·syllabus·outline..."></textarea>
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 151 characters (moodle.Files.LineLength.TooLong)
#155: ·······································min="2"·max="<?php·echo·$maxsections;·?>"·value="4">
Missing docblock for file index.php (moodle.Commenting.MissingDocblock.File)
#156: ································<small·class="form-text·text-muted">Between·2·and·<?php·echo·$maxsections;·?>·sections.</small>
Missing docblock for file index.php (moodle.Commenting.MissingDocblock.File)
#170: ········································<span·class="badge·badge-primary·rounded-circle·p-2·d-inline-flex·align-items-center·justify-content-center"·style="width:2.5rem;height:2.5rem;">
Line exceeds maximum limit of 180 characters; contains 185 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 185 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 185 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 185 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 185 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 185 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 185 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 185 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 185 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 185 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 185 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 185 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 185 characters (moodle.Files.LineLength.MaxExceeded)
#185: ····························<?php·if·($enableassignments):·?>
Missing docblock for file index.php (moodle.Commenting.MissingDocblock.File)
#190: ········································<span·class="badge·badge-secondary·rounded-circle·p-2·d-inline-flex·align-items-center·justify-content-center"·style="width:2.5rem;height:2.5rem;">
Line exceeds maximum limit of 180 characters; contains 187 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 187 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 187 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 187 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 187 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 187 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 187 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 187 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 187 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 187 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 187 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 187 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 187 characters (moodle.Files.LineLength.MaxExceeded)
#204: ····························<?php·endif;·?>
Missing docblock for file index.php (moodle.Commenting.MissingDocblock.File)
#210: ········································<span·class="badge·badge-info·rounded-circle·p-2·d-inline-flex·align-items-center·justify-content-center"·style="width:2.5rem;height:2.5rem;">
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
#229: ········································<span·class="badge·badge-info·rounded-circle·p-2·d-inline-flex·align-items-center·justify-content-center"·style="width:2.5rem;height:2.5rem;">
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 182 characters (moodle.Files.LineLength.MaxExceeded)
#252: ····································<?php·foreach·($providers·as·$p):·?>
Missing docblock for file index.php (moodle.Commenting.MissingDocblock.File)
#253: ········································<option·value="<?php·echo·$p->id;·?>"
Missing docblock for file index.php (moodle.Commenting.MissingDocblock.File)
#254: ············································<?php·echo·$p->isdefault·?·'selected'·:·'';·?>>
Missing docblock for file index.php (moodle.Commenting.MissingDocblock.File)
#255: ············································<?php·echo·format_string($p->name);
Missing docblock for file index.php (moodle.Commenting.MissingDocblock.File)
#256: ··················································echo·$p->isdefault·?·'·('·.·get_string('provider_default',·'local_courseagent')·.·')'·:·'';·?>
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
#258: ····································<?php·endforeach;·?>
Missing docblock for file index.php (moodle.Commenting.MissingDocblock.File)
#265: ····································<option·value=""><?php·print_string('provider_autoselect',·'local_courseagent');·?></option>
Missing docblock for file index.php (moodle.Commenting.MissingDocblock.File)
#291: ························CourseAgent·uses·advanced·AI·to·instantly·draft·a·comprehensive·Moodle·course·structure·based·on·your·topic·and·parameters.
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
#295: ····························<span·class="badge·badge-light·rounded-circle·p-2·mr-3·border·d-inline-flex·align-items-center·justify-content-center"·style="width:2.5rem;height:2.5rem;">
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
#300: ································<p·class="small·text-muted·mb-0">We·analyze·your·topic·and·break·it·down·into·logical·modules·and·lessons.</p>
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 142 characters (moodle.Files.LineLength.TooLong)
#304: ····························<span·class="badge·badge-light·rounded-circle·p-2·mr-3·border·d-inline-flex·align-items-center·justify-content-center"·style="width:2.5rem;height:2.5rem;">
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
#309: ································<p·class="small·text-muted·mb-0">Detailed·lesson·content,·readings,·and·summaries·are·drafted·for·each·section.</p>
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
#313: ····························<span·class="badge·badge-light·rounded-circle·p-2·mr-3·border·d-inline-flex·align-items-center·justify-content-center"·style="width:2.5rem;height:2.5rem;">
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
Line exceeds maximum limit of 180 characters; contains 183 characters (moodle.Files.LineLength.MaxExceeded)
#318: ································<p·class="small·text-muted·mb-0">You·can·edit·everything·before·finalizing·and·publishing·to·Moodle.</p>
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
#326: ································<strong>Pro·Tip:</strong>·Be·as·specific·as·possible·in·the·Topic·field.·Pasting·a·syllabus·outline·yields·the·best·results.
Line exceeds 132 characters; contains 156 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 156 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 156 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 156 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 156 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 156 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 156 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 156 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 156 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 156 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 156 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 156 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 156 characters (moodle.Files.LineLength.TooLong)
#385: <?php·echo·$OUTPUT->footer();·?>
Missing docblock for file index.php (moodle.Commenting.MissingDocblock.File)
File must end with a newline character (Generic.Files.EndFileNewline.NotFound)
A closing tag is not permitted at the end of a PHP file (Zend.Files.ClosingTag.NotAllowed)
local\courseagent\lang\en\local_courseagent.php
#1: <?php
No one-line description found in phpdocs for docblock of file local_courseagent.php (moodle.Commenting.DocblockDescription.Missing)
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
local\courseagent\lib.php
#1: <?php
No one-line description found in phpdocs for docblock of file lib.php (moodle.Commenting.DocblockDescription.Missing)
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
#10: defined('MOODLE_INTERNAL')·||·die();
Unexpected MOODLE_INTERNAL check. No side effects or multiple artifacts detected. (moodle.Files.MoodleInternal.MoodleInternalNotNeeded)
#117: }
File must end with a newline character (Generic.Files.EndFileNewline.NotFound)
local\courseagent\mycourses.php
#1: <?php
No one-line description found in phpdocs for docblock of file mycourses.php (moodle.Commenting.DocblockDescription.Missing)
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
#35: ····echo·html_writer::link(new·moodle_url('/local/courseagent/index.php'),·
Whitespace found at end of line (Squiz.WhiteSpace.SuperfluousWhitespace.EndLine)
#46: ········'Actions'
There should be a comma after the last array item in a multi-line array. (NormalizedArrays.Arrays.CommaAfterLast.MissingMultiLine)
#51: ········$course_data·=·json_decode($session->course_json);
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#52: ········$title·=·!empty($course_data->title)·?·$course_data->title·:·'Untitled·Course';
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
Variable "course_data" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#55: Ø
Whitespace found at end of line (Squiz.WhiteSpace.SuperfluousWhitespace.EndLine)
#57: ········$status_class·=·$session->status·===·'published'·?·'success'·:·
Variable "status_class" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
Whitespace found at end of line (Squiz.WhiteSpace.SuperfluousWhitespace.EndLine)
#59: ········$status_badge·=·html_writer::tag('span',·$status,·
Variable "status_badge" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
Whitespace found at end of line (Squiz.WhiteSpace.SuperfluousWhitespace.EndLine)
#60: ········································['class'·=>·"badge·badge-{$status_class}"]);
Variable "status_class" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#71: Ø
Whitespace found at end of line (Squiz.WhiteSpace.SuperfluousWhitespace.EndLine)
#72: ········$table->data[]·=·[$date,·$title,·$status_badge,·$actions];
Variable "status_badge" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
local\courseagent\preview.php
#1: <?php
No one-line description found in phpdocs for docblock of file preview.php (moodle.Commenting.DocblockDescription.Missing)
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
#37: ····<?php·if·(empty($previewdata)):·?>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#40: ············<?php·echo·get_string('no_preview_data',·'local_courseagent');·?>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#41: ············<a·href="<?php·echo·new·moodle_url('/local/courseagent/index.php');·?>"><?php·echo·get_string('create_course',·'local_courseagent');·?></a>.
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 152 characters (moodle.Files.LineLength.TooLong)
#43: ····<?php·endif;·?>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#45: ····<?php·if·(!empty($previewdata)):·?>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#46: ········<script·type="application/json"·id="ca-preview-data"><?php·echo·json_encode($previewdata);·?></script>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#52: ················<span·class="badge·badge-warning·ml-2"><?php·echo·get_string('draft_mode',·'local_courseagent');·?></span>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#55: ················<a·href="<?php·echo·new·moodle_url('/local/courseagent/index.php');·?>"·class="btn·btn-outline-secondary·btn-sm·mr-2">
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
#57: ····················<?php·echo·get_string('back_to_create',·'local_courseagent');·?>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#61: ····················<?php·echo·get_string('publish_to_moodle',·'local_courseagent');·?>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#71: ····················<h5·class="mb-1"><?php·echo·get_string('course_builder',·'local_courseagent');·?></h5>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#73: ························<span·class="ca-status-dot"></span>·<?php·echo·get_string('ai_assistant_active',·'local_courseagent');·?>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#87: ························<h5·class="mb-0"><?php·echo·get_string('agent_assistant',·'local_courseagent');·?></h5>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#100: ························placeholder="<?php·echo·get_string('chat_placeholder',·'local_courseagent');·?>"></textarea>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#105: ················<p·class="ca-chat-disclaimer"><?php·echo·get_string('chat_disclaimer',·'local_courseagent');·?></p>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#110: ····<?php·endif;·?>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
#113: <?php·echo·$OUTPUT->footer();·?>
Missing docblock for file preview.php (moodle.Commenting.MissingDocblock.File)
A closing tag is not permitted at the end of a PHP file (Zend.Files.ClosingTag.NotAllowed)
local\courseagent\providers.php
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
#36: //·------------------------------------------------------------------·//
Comment separators are not allowed to contain other chars buy hyphens (-). Found: (/) (moodle.Commenting.InlineComment.IncorrectCommentSeparator)
#38: //·------------------------------------------------------------------·//
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#68: //·------------------------------------------------------------------·//
Comment separators are not allowed to contain other chars buy hyphens (-). Found: (/) (moodle.Commenting.InlineComment.IncorrectCommentSeparator)
#70: //·------------------------------------------------------------------·//
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#81: //·------------------------------------------------------------------·//
Comment separators are not allowed to contain other chars buy hyphens (-). Found: (/) (moodle.Commenting.InlineComment.IncorrectCommentSeparator)
#83: //·------------------------------------------------------------------·//
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#92: //·------------------------------------------------------------------·//
Comment separators are not allowed to contain other chars buy hyphens (-). Found: (/) (moodle.Commenting.InlineComment.IncorrectCommentSeparator)
#94: //·------------------------------------------------------------------·//
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#114: //·------------------------------------------------------------------·//
Comment separators are not allowed to contain other chars buy hyphens (-). Found: (/) (moodle.Commenting.InlineComment.IncorrectCommentSeparator)
#116: //·------------------------------------------------------------------·//
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#124: $qf_marker·=·'_qf__local_courseagent_form_provider_form';
Variable "qf_marker" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#125: $form_submitted·=·optional_param($qf_marker,·null,·PARAM_RAW)·!==·null;
Variable "form_submitted" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
Variable "qf_marker" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#142: }·else·if·($isadding·||·$form_submitted)·{
Variable "form_submitted" must not contain underscores. (moodle.NamingConventions.ValidVariableName.VariableNameUnderscore)
#153: ········//·Debug:·Uncomment·to·see·what·data·is·received
This comment is 46% valid code; is this commented out code? (Squiz.PHP.CommentedOutCode.Found)
#154: ········//·echo·$OUTPUT->header();·echo·'<pre>';·print_r($data);·echo·'</pre>';·echo·$OUTPUT->footer();·exit;
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#157: ········$modelsRaw·=·$data->models_json·??·'[]';
Variable "modelsRaw" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#158: ········$models····=·json_decode($modelsRaw,·true);
Variable "modelsRaw" must be all lower-case (moodle.NamingConventions.ValidVariableName.VariableNameLowerCase)
#184: ················//·redirect(new·moodle_url('/local/courseagent/providers.php'),·"Created·provider·ID:·"·.·$newid,·null,·\core\output\notification::NOTIFY_SUCCESS);
Line exceeds 132 characters; contains 163 characters (moodle.Files.LineLength.TooLong)
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#192: ················//·Show·error·if·create·failed
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#235: //·------------------------------------------------------------------·//
Comment separators are not allowed to contain other chars buy hyphens (-). Found: (/) (moodle.Commenting.InlineComment.IncorrectCommentSeparator)
#237: //·------------------------------------------------------------------·//
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#241: //·"Add·New·Provider"·button.
Inline comments must start with a capital letter, digit or 3-dots sequence (moodle.Commenting.InlineComment.NotCapital)
#278: ················$modelshtml·.=·'·'·.·html_writer::tag('span',·'+'·.·($modelcount·-·1)·.·'·more',·['class'·=>·'badge·badge-light·border·text-muted']);
Line exceeds 132 characters; contains 149 characters (moodle.Files.LineLength.TooLong)
#321: ················new·moodle_url('/local/courseagent/providers.php',·['action'·=>·'setdefault',·'id'·=>·$p->id,·'sesskey'·=>·sesskey()]),
Line exceeds 132 characters; contains 135 characters (moodle.Files.LineLength.TooLong)
#347: ················html_writer::tag('code',·strlen($p->baseurl)·>·45·?·substr($p->baseurl,·0,·45)·.·'…'·:·$p->baseurl,·['class'·=>·'small']),
Line exceeds 132 characters; contains 138 characters (moodle.Files.LineLength.TooLong)
#359: //·------------------------------------------------------------------·//
Comment separators are not allowed to contain other chars buy hyphens (-). Found: (/) (moodle.Commenting.InlineComment.IncorrectCommentSeparator)
#361: //·------------------------------------------------------------------·//
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#368: //·------------------------------------------------------------------·//
Comment separators are not allowed to contain other chars buy hyphens (-). Found: (/) (moodle.Commenting.InlineComment.IncorrectCommentSeparator)
#370: //·------------------------------------------------------------------·//
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#399: ················console.group('%c[Course·Agent]·Provider·Test·Connection·Debug',·'font-size:·14px;·font-weight:·bold;·color:·#0066cc;');
Line exceeds 132 characters; contains 136 characters (moodle.Files.LineLength.TooLong)
#460: ································console.log('Candidates·Count:',·data.debug.response_processing.response_structure.candidates_count);
Line exceeds 132 characters; contains 133 characters (moodle.Files.LineLength.TooLong)
#461: ································console.log('Candidate[0]·Keys:',·data.debug.response_processing.response_structure.candidate_0_keys);
Line exceeds 132 characters; contains 134 characters (moodle.Files.LineLength.TooLong)
#462: ································console.log('Candidate[0]·Content·Keys:',·data.debug.response_processing.response_structure.candidate_0_content_keys);
Line exceeds 132 characters; contains 150 characters (moodle.Files.LineLength.TooLong)
#463: ································console.log('Candidate[0]·Parts·Type:',·data.debug.response_processing.response_structure.candidate_0_parts_type);
Line exceeds 132 characters; contains 146 characters (moodle.Files.LineLength.TooLong)
#464: ································console.log('Candidate[0]·Parts·Count:',·data.debug.response_processing.response_structure.candidate_0_parts_count);
Line exceeds 132 characters; contains 148 characters (moodle.Files.LineLength.TooLong)
#470: ································console.log('Choice[0]·Message·Keys:',·data.debug.response_processing.response_structure.choice_0_message_keys);
Line exceeds 132 characters; contains 144 characters (moodle.Files.LineLength.TooLong)
#478: ····················console.log('Raw·Response·(first·500·chars):',·data.debug.response_raw·?·data.debug.response_raw.substring(0,·500)·:·'(none)');
Line exceeds 132 characters; contains 147 characters (moodle.Files.LineLength.TooLong)
#507: ························console.log('%c5.·Request·Duration:·'·+·data.debug.timing.request_duration_ms·+·'ms',·'color:·#9900cc;·font-weight:·bold;');
Line exceeds 132 characters; contains 148 characters (moodle.Files.LineLength.TooLong)
local\courseagent\settings.php
#103: }
File must end with a newline character (Generic.Files.EndFileNewline.NotFound)
local\courseagent\version.php
#1: <?php
No one-line description found in phpdocs for docblock of file version.php (moodle.Commenting.DocblockDescription.Missing)
#2: //·This·file·is·part·of·Course·Agent·-·AI·Course·Creator·Plugin·for·Moodle
Inline comments must end in full-stops, exclamation marks, or question marks (moodle.Commenting.InlineComment.InvalidEndChar)
#3: Ø
Comment does not contain full Moodle boilerplate (moodle.Files.BoilerplateComment.CommentEndedTooSoon)
Checks code against some aspects of the Moodle coding guidelines.

Enter a path relative to the Moodle code root, for example: local/codechecker.

You can enter either a specific PHP file, or to a folder to check all the files it contains. Multiple entries are supported (files or folders), one per line.

To exclude files, a comma separated list of substr matching paths can be used, for example: db, backup/*1, *lib*. Asterisks are allowed as wildchars at any place.