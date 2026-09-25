/* Anteprima Markdown client-side (solo preview: il rendering autorevole e'
   MarkdownService lato server). Stesso subset: escape integrale, poi trasformazioni. */
(function () {
    var ta = document.getElementById('content_markdown');
    var preview = document.getElementById('md-preview');
    if (!ta || !preview) return;

    function esc(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function inline(t) {
        t = t.replace(/`([^`]+)`/g, '<code>$1</code>');
        t = t.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        t = t.replace(/\*([^*\n]+)\*/g, '<em>$1</em>');
        t = t.replace(/!\[([^\]]*)\]\((\/attachments\/\d+|https?:\/\/[^)\s]+)\)/g,
             '<img src="$2" alt="$1" loading="lazy">');
        t = t.replace(/\[([^\]]+)\]\((\/attachments\/\d+|https?:\/\/[^)\s]+)\)/g,
             '<a href="$2" rel="noopener noreferrer nofollow">$1</a>');
        return t;
    }

    function md(src) {
        var lines = esc(src.replace(/\r\n?/g, '\n')).split('\n');
        var out = '', inCode = false, codeBuf = [], para = [], list = [], quote = [];

        function flushPara() { if (para.length) { out += '<p>' + inline(para.join('<br>')) + '</p>'; para = []; } }
        function flushList() {
            if (list.length) {
                out += '<ul>' + list.map(function (i) { return '<li>' + inline(i) + '</li>'; }).join('') + '</ul>';
                list = [];
            }
        }
        function flushQuote() {
            if (quote.length) { out += '<blockquote>' + inline(quote.join('<br>')) + '</blockquote>'; quote = []; }
        }
        function flushAll() { flushPara(); flushList(); flushQuote(); }

        lines.forEach(function (line) {
            var m;
            if (/^```/.test(line)) {
                flushAll();
                if (inCode) { out += '<pre><code>' + codeBuf.join('\n') + '</code></pre>'; codeBuf = []; inCode = false; }
                else inCode = true;
                return;
            }
            if (inCode) { codeBuf.push(line); return; }
            if (line.trim() === '') { flushAll(); return; }
            if ((m = line.match(/^(#{1,6})\s+(.*)$/))) {
                flushAll();
                out += '<h' + m[1].length + '>' + inline(m[2]) + '</h' + m[1].length + '>';
                return;
            }
            if (/^-{3,}$/.test(line.trim())) { flushAll(); out += '<hr>'; return; }
            if ((m = line.match(/^&gt;\s?(.*)$/))) { flushPara(); flushList(); quote.push(m[1]); return; }
            if ((m = line.match(/^[-*]\s+(.*)$/))) { flushPara(); flushQuote(); list.push(m[1]); return; }
            para.push(line);
        });
        if (inCode && codeBuf.length) out += '<pre><code>' + codeBuf.join('\n') + '</code></pre>';
        flushAll();
        return out;
    }

    var timer;
    function render() { preview.innerHTML = md(ta.value); }
    ta.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(render, 150); });
    render();
})();