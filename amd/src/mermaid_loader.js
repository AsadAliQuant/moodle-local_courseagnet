define(['jquery'], function($) {

    /**
     * Strip markdown code fences that AI models often wrap around diagram code.
     * Handles: ```mermaid ... ```, ``` ... ```, and leading/trailing whitespace.
     *
     * @param {string} raw Raw text content of the .mermaid element
     * @return {string} Clean diagram-only code
     */
    const sanitize = function(raw) {
        return raw
            .replace(/^```mermaid\s*/i, '')
            .replace(/^```\s*/m, '')
            .replace(/```\s*$/m, '')
            .trim();
    };

    const escapeHtml = function(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    /**
     * Validate and render a single .mermaid element.
     * If the diagram has a syntax error, replace it with a clean error box
     * showing the raw code so the author can debug — never show Mermaid's
     * ugly "Syntax error in text" message to students.
     *
     * @param {Element} el A single DOM element with class="mermaid"
     */
    const renderOne = async function(el) {
        // Skip elements already processed by a previous run() call.
        if (el.getAttribute('data-processed') === 'true') {
            return;
        }

        const raw = el.textContent || el.innerText || '';
        const code = sanitize(raw);

        if (!code) {
            return; // Empty block — nothing to render.
        }

        try {
            // Validate BEFORE rendering so we get a clean error, not Mermaid's
            // internal "Syntax error in text" bleed-through.
            await mermaid.parse(code);

            // Replace element content with sanitized code (removes stray fences).
            el.textContent = code;

            // Render this single element in isolation.
            await mermaid.run({ nodes: [el] });

        } catch (err) {
            // Extract a readable error message; Mermaid parse errors are verbose.
            let msg = 'Syntax error — check the diagram code below.';
            if (err && err.message) {
                // Mermaid parse errors often start with a useful first line.
                msg = err.message.split('\n')[0].trim().substring(0, 160);
            }

            // Render a clean error box with the raw code so the author can fix it.
            // Students only see a non-breaking placeholder; never the raw mermaid text.
            el.innerHTML =
                '<div style="border:1px solid #e07c24;border-radius:6px;padding:14px 16px;' +
                'background:#fffbf5;font-family:inherit;">' +
                '<p style="margin:0 0 6px;color:#b45309;font-weight:600;font-size:.9em;">' +
                '⚠ Diagram could not be rendered</p>' +
                '<p style="margin:0 0 10px;color:#78716c;font-size:.82em;">' + escapeHtml(msg) + '</p>' +
                '<details style="cursor:pointer;">' +
                '<summary style="color:#a16207;font-size:.82em;user-select:none;">Show diagram source</summary>' +
                '<pre style="margin:8px 0 0;font-size:.78em;background:#f8f7f4;padding:10px;' +
                'border-radius:4px;overflow-x:auto;white-space:pre-wrap;color:#44403c;">' +
                escapeHtml(code) + '</pre></details></div>';

            // Remove data-processed so admins can fix + reload without clearing cache.
            el.removeAttribute('data-processed');
        }
    };

    return {
        /**
         * Entry point called by lib.php on every Moodle page.
         * Skips loading the heavy mermaid library if no .mermaid elements exist.
         *
         * @param {string} mermaidUrl Absolute URL to the bundled mermaid.min.js
         */
        init: function(mermaidUrl) {
            $(document).ready(function() {
                if ($('.mermaid').length === 0) {
                    return; // No diagrams on this page — do nothing.
                }

                const runAll = function() {
                    mermaid.initialize({
                        startOnLoad: false,
                        theme: 'default',
                        securityLevel: 'loose'
                    });

                    // Process each diagram independently.
                    // One broken diagram will NOT prevent the others from rendering.
                    document.querySelectorAll('.mermaid').forEach(function(el) {
                        renderOne(el);
                    });
                };

                if (typeof mermaid === 'undefined') {
                    var script = document.createElement('script');
                    script.src = mermaidUrl;
                    script.onload = runAll;
                    script.onerror = function() {
                        // Mermaid CDN failed — silently leave diagrams as code blocks.
                        console.warn('[CourseAgent] Failed to load mermaid.js from: ' + mermaidUrl);
                    };
                    document.head.appendChild(script);
                } else {
                    runAll();
                }
            });
        }
    };
});
