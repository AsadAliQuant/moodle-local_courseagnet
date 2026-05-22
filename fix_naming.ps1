# PowerShell script to fix snake_case to camelCase/PascalCase naming
# Run from the plugin directory

$conversions = @{
    # Class names: snake_case -> PascalCase
    'class api ' = 'class Api '
    'class provider ' = 'class Provider '
    'class provider_form ' = 'class ProviderForm '
    'class navigation ' = 'class Navigation '
    'class output ' = 'class Output '
    'class extractor ' = 'class Extractor '
}

$methodConversions = @{
    'generate_course_outline' = 'generateCourseOutline'
    'plan_course_outline' = 'planCourseOutline'
    'build_plan_prompt' = 'buildPlanPrompt'
    'build_fallback_providers' = 'buildFallbackProviders'
    'is_rate_limit_error' = 'isRateLimitError'
    'build_generation_prompt' = 'buildGenerationPrompt'
    'publish_course' = 'publishCourse'
    'create_cm_stub' = 'createCmStub'
    'place_cm_in_section' = 'placeCmInSection'
    'create_lesson_page' = 'createLessonPage'
    'create_quiz' = 'createQuiz'
    'get_or_create_question_category' = 'getOrCreateQuestionCategory'
    'create_multichoice_question' = 'createMultichoiceQuestion'
    'create_assignment' = 'createAssignment'
    'create_h5p_activities' = 'createH5pActivities'
    'build_h5p_package' = 'buildH5pPackage'
    'create_h5p_module' = 'createH5pModule'
    'write_progress' = 'writeProgress'
    'edit_item' = 'editItem'
    'build_edit_prompt' = 'buildEditPrompt'
    'merge_edited_item' = 'mergeEditedItem'
    'ai_assist' = 'aiAssist'
    'detect_section_reference' = 'detectSectionReference'
    'build_context_prompt' = 'buildContextPrompt'
    'format_section_full' = 'formatSectionFull'
    'get_assistant_system_prompt' = 'getAssistantSystemPrompt'
    'extract_json_object' = 'extractJsonObject'
    'sanitize_json_strings' = 'sanitizeJsonStrings'
    'parse_json_response' = 'parseJsonResponse'
    'merge_delta' = 'mergeDelta'
    'build_success_message' = 'buildSuccessMessage'
    'get_encryption_key' = 'getEncryptionKey'
    'encrypt_apikey' = 'encryptApikey'
    'decrypt_apikey' = 'decryptApikey'
    'get_all' = 'getAll'
    'get_default' = 'getDefault'
    'get_config' = 'getConfig'
    'set_default' = 'setDefault'
    'set_enabled' = 'setEnabled'
    'test_connection_raw' = 'testConnectionRaw'
    'test_connection' = 'testConnection'
    'call_api' = 'callApi'
    'call_api_with_history' = 'callApiWithHistory'
    'render_models_widget' = 'renderModelsWidget'
    'render_test_button' = 'renderTestButton'
    'extend_primary_navigation' = 'extendPrimaryNavigation'
    'before_footer' = 'beforeFooter'
    'get_metadata' = 'getMetadata'
    'get_contexts_for_userid' = 'getContextsForUserid'
    'export_user_data' = 'exportUserData'
    'delete_data_for_all_users_in_context' = 'deleteDataForAllUsersInContext'
    'delete_data_for_user' = 'deleteDataForUser'
}

$phpFiles = @(
    (Get-ChildItem -Path classes -Filter '*.php' -Recurse -ErrorAction SilentlyContinue | ForEach-Object { $_.FullName })
    (Get-ChildItem -Path db -Filter '*.php' -Recurse -ErrorAction SilentlyContinue | ForEach-Object { $_.FullName })
    (Get-ChildItem -Filter '*.php' -ErrorAction SilentlyContinue | ForEach-Object { $_.FullName })
) | Where-Object { $_ -and (Test-Path $_) -and $_ -notmatch 'fix_naming.ps1|fix_naming.php' }

Write-Host "Starting rename process..."
$totalUpdates = 0

foreach ($file in $phpFiles) {
    if (-not (Test-Path $file)) { continue }

    $content = Get-Content $file -Raw
    $originalContent = $content

    # Replace class definitions
    foreach ($old in $conversions.Keys) {
        $new = $conversions[$old]
        $count = (($content | Select-String -Pattern ([regex]::Escape($old)) -AllMatches) | Measure-Object).Count
        if ($count -gt 0) {
            $content = $content -replace [regex]::Escape($old), $new
            Write-Host "  [$(Split-Path -Leaf $file)] Class rename: $old -> $new ($count times)"
            $totalUpdates += $count
        }
    }

    # Replace method definitions and calls
    foreach ($oldMethod in $methodConversions.Keys) {
        $newMethod = $methodConversions[$oldMethod]

        # Match both ->method_name and function method_name( and method_name()
        $patterns = @(
            "->$oldMethod"
            "::$oldMethod"
            "function $oldMethod("
            "$oldMethod()"
        )

        foreach ($pattern in $patterns) {
            $count = (($content | Select-String -Pattern ([regex]::Escape($pattern)) -AllMatches) | Measure-Object).Count
            if ($count -gt 0) {
                $newPattern = $pattern -replace [regex]::Escape($oldMethod), $newMethod
                $content = $content -replace [regex]::Escape($pattern), $newPattern
                $totalUpdates += $count
            }
        }
    }

    if ($content -ne $originalContent) {
        Set-Content $file $content -Encoding UTF8
        Write-Host "[+] Updated $(Split-Path -Leaf $file)"
    }
}

Write-Host "`n[+] Total updates: $totalUpdates"
Write-Host "[+] Run: phpcs --standard=phpcs.xml . to verify"
