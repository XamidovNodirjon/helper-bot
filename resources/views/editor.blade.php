<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web IDE - Kod Muharriri</title>
    <!-- Modern Premium Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- CSRF Token for Secure POST Requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        :root {
            --bg-dark: #090d16;
            --bg-sidebar: #0f172a;
            --border-color: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --editor-bg: #1e1e1e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-primary);
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* IDE Layout */
        .ide-container {
            display: flex;
            flex: 1;
            height: 100%;
            overflow: hidden;
        }

        /* Sidebar File Explorer */
        .sidebar {
            width: 280px;
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .sidebar-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-header h2 {
            font-size: 16px;
            font-weight: 700;
            background: linear-gradient(90deg, #3b82f6, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-refresh {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .btn-refresh:hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.05);
        }

        .file-tree-container {
            flex: 1;
            overflow-y: auto;
            padding: 12px 16px;
        }

        /* Tree Styles */
        .tree-node {
            user-select: none;
            margin-left: 12px;
        }

        .tree-node.root {
            margin-left: 0;
        }

        .node-row {
            display: flex;
            align-items: center;
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 400;
            color: var(--text-secondary);
            transition: all 0.2s;
            gap: 8px;
        }

        .node-row:hover {
            background-color: rgba(255, 255, 255, 0.04);
            color: var(--text-primary);
        }

        .node-row.active {
            background-color: rgba(59, 130, 246, 0.15);
            color: var(--text-primary);
            font-weight: 500;
        }

        .node-children {
            display: none;
        }

        .node-children.expanded {
            display: block;
        }

        .icon-chevron {
            font-size: 14px;
            transition: transform 0.2s;
            flex-shrink: 0;
        }

        .icon-chevron.open {
            transform: rotate(90deg);
        }

        .icon-node {
            color: var(--text-secondary);
            flex-shrink: 0;
        }

        .node-row.active .icon-node {
            color: var(--accent-blue);
        }

        /* Editor Area */
        .editor-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--editor-bg);
            overflow: hidden;
            border-right: 1px solid var(--border-color);
        }

        .editor-tabs {
            height: 40px;
            background-color: #181818;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .editor-tabs::-webkit-scrollbar {
            display: none;
        }

        .tab {
            display: flex;
            align-items: center;
            padding: 0 16px;
            height: 100%;
            border-right: 1px solid var(--border-color);
            background-color: #141414;
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            gap: 8px;
            transition: all 0.2s;
        }

        .tab.active {
            background-color: var(--editor-bg);
            color: var(--text-primary);
            border-bottom: 2px solid var(--accent-blue);
        }

        .tab-close {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .tab-close:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--accent-red);
        }

        /* Monaco Editor Container */
        .editor-container {
            flex: 1;
            width: 100%;
            height: 100%;
            position: relative;
        }

        /* Status Bar */
        .status-bar {
            height: 28px;
            background-color: #1e1e1e;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            font-size: 11px;
            color: var(--text-secondary);
        }

        .status-left, .status-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .save-status {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .save-status.unsaved {
            color: #eab308;
        }

        .save-status.saving {
            color: var(--accent-blue);
        }

        .save-status.saved {
            color: var(--accent-green);
        }

        /* Right Panel - Assistant & Runner */
        .helper-panel {
            width: 380px;
            background-color: var(--bg-sidebar);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            overflow: hidden;
        }

        .panel-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .panel-header h2 {
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Action Buttons */
        .action-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .action-group h3 {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .btn-action {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 14px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
        }

        .btn-action:hover {
            background: rgba(59, 130, 246, 0.1);
            border-color: var(--accent-blue);
            color: var(--text-primary);
        }

        .btn-action i {
            color: var(--accent-blue);
        }

        /* Console Output area */
        .console-container {
            display: flex;
            flex-direction: column;
            background-color: #020617;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            height: 300px;
            flex-shrink: 0;
        }

        .console-header {
            background-color: rgba(255, 255, 255, 0.02);
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .console-header h3 {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .console-body {
            flex: 1;
            padding: 14px;
            font-family: 'Fira Code', monospace;
            font-size: 11px;
            line-height: 1.5;
            color: #38bdf8;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        /* Loader Overlay inside editor */
        .loader-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.8);
            z-index: 10;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 12px;
        }

        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top: 3px solid var(--accent-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .spinner-small {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-top: 2px solid var(--accent-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Info Card */
        .info-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.4) 0%, rgba(15, 23, 42, 0.6) 100%);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
        }

        .info-card h4 {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-card p {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        /* Welcome Screen for Editor */
        .welcome-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--editor-bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 5;
            text-align: center;
            padding: 40px;
        }

        .welcome-screen h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
            background: linear-gradient(90deg, #3b82f6, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-screen p {
            font-size: 14px;
            color: var(--text-secondary);
            max-width: 420px;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .shortcut-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            max-width: 320px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            padding: 16px;
            border-radius: 10px;
        }

        .shortcut-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
        }

        .shortcut-key {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            border-bottom: 1.5px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>

    <div class="ide-container">
        <!-- Sidebar File Tree -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i data-lucide="code-2"></i> WEB IDE</h2>
                <button class="btn-refresh" id="btn-refresh" title="Fayllar ro'yxatini yangilash">
                    <i data-lucide="refresh-cw" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
            
            <div class="file-tree-container" id="file-tree">
                <!-- Populated dynamically via JS -->
                <div style="display: flex; justify-content: center; align-items: center; height: 100%;">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>

        <!-- Editor Workspace Area -->
        <div class="editor-area">
            <!-- Tabs Bar -->
            <div class="editor-tabs" id="editor-tabs">
                <!-- Open file tabs dynamically inserted -->
            </div>

            <!-- Monaco Editor Container -->
            <div class="editor-container">
                <div class="loader-overlay" id="editor-loader">
                    <div class="spinner"></div>
                    <p style="margin-top: 8px; font-size: 13px; color: var(--text-secondary)">Fayl yuklanmoqda...</p>
                </div>
                
                <!-- Welcome Screen -->
                <div class="welcome-screen" id="welcome-screen">
                    <i data-lucide="terminal" style="width: 48px; height: 48px; color: var(--accent-blue); margin-bottom: 16px;"></i>
                    <h1>Web IDE Muharriri</h1>
                    <p>Telegram Bot loyihangiz fayllarini tahrirlash uchun chap tarafdagi fayl daraxtidan istalgan faylni tanlang.</p>
                    <div class="shortcut-list">
                        <div class="shortcut-item">
                            <span>Faylni saqlash</span>
                            <span class="shortcut-key">Ctrl + S</span>
                        </div>
                        <div class="shortcut-item">
                            <span>Qidiruv</span>
                            <span class="shortcut-key">Ctrl + F</span>
                        </div>
                        <div class="shortcut-item">
                            <span>Kod almashtirish</span>
                            <span class="shortcut-key">Ctrl + H</span>
                        </div>
                    </div>
                </div>

                <div id="monaco-editor-instance" style="width: 100%; height: 100%;"></div>
            </div>

            <!-- Status Bar -->
            <div class="status-bar">
                <div class="status-left">
                    <span id="status-filepath">Tanlangan fayl yo'q</span>
                    <span class="save-status" id="status-savestate"></span>
                </div>
                <div class="status-right">
                    <span id="status-position">Line 1, Col 1</span>
                    <span>UTF-8</span>
                    <span id="status-language">Plain Text</span>
                </div>
            </div>
        </div>

        <!-- Helper Actions & Command Runner Panel -->
        <div class="helper-panel">
            <div class="panel-header">
                <h2><i data-lucide="sparkles" style="color: #eab308;"></i> AI Yordamchi & Asboblar</h2>
            </div>
            
            <div class="panel-body">
                <!-- Console Output Container -->
                <div class="console-container">
                    <div class="console-header">
                        <h3><i data-lucide="terminal"></i> Terminal va Buyruq natijasi</h3>
                        <div class="spinner-small" id="console-loader"></div>
                    </div>
                    <div class="console-body" id="console-output">Buyruq natijalari bu yerda chiqadi...</div>
                </div>

                <!-- Action Group -->
                <div class="action-group">
                    <h3>Tezkor buyruqlar</h3>
                    <button class="btn-action" onclick="runCommand('clear_cache')">
                        <i data-lucide="trash-2"></i>
                        <span>Artisan keshini tozalash</span>
                    </button>
                    <button class="btn-action" onclick="runCommand('route_list')">
                        <i data-lucide="list"></i>
                        <span>Route listini ko'rish</span>
                    </button>
                    <button class="btn-action" onclick="runCommand('migrate')">
                        <i data-lucide="database"></i>
                        <span>Migratsiyalarni ishga tushirish</span>
                    </button>
                    <button class="btn-action" onclick="runCommand('view_logs')">
                        <i data-lucide="file-warning"></i>
                        <span>Laravel xatolik loglarini ko'rish</span>
                    </button>
                </div>

                <div class="action-group">
                    <h3>Skraper sinovlari</h3>
                    <button class="btn-action" onclick="runCommand('test_scraper')">
                        <i data-lucide="play-circle"></i>
                        <span>OLX Kvartira Skraperini sinash</span>
                    </button>
                    <button class="btn-action" onclick="runCommand('test_scraper_office')">
                        <i data-lucide="play-circle"></i>
                        <span>OLX Ofis Skraperini sinash</span>
                    </button>
                </div>

                <!-- AI Tips Card -->
                <div class="info-card">
                    <h4><i data-lucide="info" style="color: var(--accent-blue);"></i> Muhim ma'lumot</h4>
                    <p>Loyihaga kiritilgan barcha o'zgarishlar darhol mahalliy fayllarda saqlanadi. Kodni tahrirlashda sintaksis xatoliklarga yo'l qo'ymaslikka e'tibor qarating.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Monaco Editor Source Loading from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/require.js/2.3.6/require.min.js"></script>
    <script>
        let editor = null;
        let activeFile = null;
        let openTabs = [];
        let isUnsaved = false;

        // Initialize Lucide Icons
        lucide.createIcons();

        // Configure require.js for Monaco
        require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs' }});

        require(['vs/editor/editor.main'], function() {
            // Load file tree upon Monaco initialization
            loadFileTree();
        });

        // 1. Fetch File Tree structure
        function loadFileTree() {
            fetch("{{ route('editor.api.files') }}")
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('file-tree');
                    container.innerHTML = '';
                    renderTreeNodes(data, container, true);
                    lucide.createIcons();
                })
                .catch(err => {
                    console.error("Daraxt yuklashda xatolik:", err);
                    document.getElementById('file-tree').innerHTML = '<div style="color: var(--accent-red); font-size:12px;">Fayl tizimini yuklashda xatolik yuz berdi.</div>';
                });
        }

        // 2. Recursive HTML tree view renderer
        function renderTreeNodes(nodes, container, isRoot = false) {
            const rootDiv = document.createElement('div');
            rootDiv.className = isRoot ? 'tree-node root' : 'tree-node';

            nodes.forEach(node => {
                const nodeItem = document.createElement('div');
                nodeItem.className = 'node-item';

                const row = document.createElement('div');
                row.className = 'node-row';
                row.dataset.path = node.path;
                
                // Add icons based on type
                let chevronHtml = '';
                let iconName = 'file';
                
                if (node.type === 'directory') {
                    chevronHtml = '<i data-lucide="chevron-right" class="icon-chevron"></i>';
                    iconName = 'folder';
                } else {
                    // Dynamic file extension styling
                    const ext = node.name.split('.').pop().toLowerCase();
                    if (ext === 'php') iconName = 'file-code-2';
                    else if (ext === 'py') iconName = 'terminal';
                    else if (ext === 'json') iconName = 'file-json';
                    else if (ext === 'css') iconName = 'file-text';
                    else if (ext === 'js') iconName = 'file-type-2';
                }

                row.innerHTML = `
                    ${chevronHtml}
                    <i data-lucide="${iconName}" class="icon-node"></i>
                    <span class="node-label">${node.name}</span>
                `;

                nodeItem.appendChild(row);

                if (node.type === 'directory') {
                    const childrenContainer = document.createElement('div');
                    childrenContainer.className = 'node-children';
                    
                    row.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const childrenNode = row.nextElementSibling;
                        const chevron = row.querySelector('.icon-chevron');
                        
                        if (childrenNode.classList.contains('expanded')) {
                            childrenNode.classList.remove('expanded');
                            chevron.classList.remove('open');
                        } else {
                            childrenNode.classList.add('expanded');
                            chevron.classList.add('open');
                        }
                    });

                    renderTreeNodes(node.children, childrenContainer);
                    nodeItem.appendChild(childrenContainer);
                } else {
                    // File selection event
                    row.addEventListener('click', (e) => {
                        e.stopPropagation();
                        openFile(node.path);
                    });
                }

                rootDiv.appendChild(nodeItem);
            });

            container.appendChild(rootDiv);
        }

        // 3. Load Specific File
        function openFile(path) {
            // Unsaved changes check
            if (isUnsaved) {
                if (!confirm("Faylda saqlanmagan o'zgarishlar mavjud. Saqlamasdan yopishga aminmisiz?")) {
                    return;
                }
            }

            document.getElementById('editor-loader').style.display = 'flex';
            document.getElementById('welcome-screen').style.display = 'none';

            fetch(`{{ route('editor.api.file') }}?path=${encodeURIComponent(path)}`)
                .then(res => res.json())
                .then(file => {
                    if (file.error) {
                        alert(file.error);
                        document.getElementById('editor-loader').style.display = 'none';
                        return;
                    }

                    activeFile = file.path;
                    isUnsaved = false;
                    updateSaveStatus('saved');

                    // Setup tab bar
                    addTab(file.path, file.name);

                    // Determine language mode
                    let language = 'plaintext';
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (ext === 'php') language = 'php';
                    else if (ext === 'py') language = 'python';
                    else if (ext === 'json') language = 'json';
                    else if (ext === 'css') language = 'css';
                    else if (ext === 'js') language = 'javascript';
                    else if (ext === 'html') language = 'html';
                    else if (ext === 'md') language = 'markdown';
                    else if (ext === 'sh') language = 'shell';

                    // Update Status bar
                    document.getElementById('status-filepath').textContent = file.path;
                    document.getElementById('status-language').textContent = language.toUpperCase();

                    // Active tree row highlighting
                    document.querySelectorAll('.node-row').forEach(row => {
                        if (row.dataset.path === file.path) {
                            row.classList.add('active');
                        } else {
                            row.classList.remove('active');
                        }
                    });

                    // Initialize or update Monaco Editor model
                    const container = document.getElementById('monaco-editor-instance');
                    
                    if (!editor) {
                        editor = monaco.editor.create(container, {
                            value: file.content,
                            language: language,
                            theme: 'vs-dark',
                            automaticLayout: true,
                            fontFamily: 'Fira Code, monospace',
                            fontSize: 13,
                            minimap: { enabled: true },
                            tabSize: 4
                        });

                        // Unsaved state listener on keystroke
                        editor.onDidChangeModelContent(() => {
                            isUnsaved = true;
                            updateSaveStatus('unsaved');
                        });

                        // Position Change listener
                        editor.onDidChangeCursorPosition((e) => {
                            document.getElementById('status-position').textContent = `Line ${e.position.lineNumber}, Col ${e.position.column}`;
                        });

                        // Ctrl + S save shortcut inside monaco
                        editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, function() {
                            saveCurrentFile();
                        });
                    } else {
                        // Dispose old model if exists to prevent memory leaks
                        const oldModel = editor.getModel();
                        if (oldModel) oldModel.dispose();

                        const newModel = monaco.editor.createModel(file.content, language);
                        editor.setModel(newModel);
                    }

                    document.getElementById('editor-loader').style.display = 'none';
                })
                .catch(err => {
                    console.error("Fayl yuklashda xatolik:", err);
                    alert("Faylni ochib bo'lmadi.");
                    document.getElementById('editor-loader').style.display = 'none';
                });
        }

        // 4. Save Current File content via POST
        function saveCurrentFile() {
            if (!activeFile || !editor) return;

            const content = editor.getValue();
            updateSaveStatus('saving');

            fetch("{{ route('editor.api.save') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    path: activeFile,
                    content: content
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    updateSaveStatus('unsaved');
                } else {
                    isUnsaved = false;
                    updateSaveStatus('saved');
                }
            })
            .catch(err => {
                console.error("Saqlashda xatolik:", err);
                alert("Faylni saqlab bo'lmadi.");
                updateSaveStatus('unsaved');
            });
        }

        // Update Save indicator UI
        function updateSaveStatus(state) {
            const el = document.getElementById('status-savestate');
            el.className = 'save-status ' + state;
            if (state === 'saved') {
                el.textContent = '● Saqlandi';
            } else if (state === 'saving') {
                el.textContent = '○ Saqlanmoqda...';
            } else if (state === 'unsaved') {
                el.textContent = '● Saqlanmagan o\'zgarishlar';
            }
        }

        // 5. Add and switch file tabs
        function addTab(path, name) {
            const tabsContainer = document.getElementById('editor-tabs');
            
            // Check if tab already exists
            const existingTab = document.querySelector(`.tab[data-path="${path}"]`);
            if (existingTab) {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                existingTab.classList.add('active');
                return;
            }

            // Create new tab element
            const tab = document.createElement('div');
            tab.className = 'tab active';
            tab.dataset.path = path;
            tab.innerHTML = `
                <span>${name}</span>
                <span class="tab-close" onclick="closeTab(event, '${path}')">×</span>
            `;

            tab.addEventListener('click', (e) => {
                if (e.target.classList.contains('tab-close')) return;
                openFile(path);
            });

            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            tabsContainer.appendChild(tab);
        }

        // Close tab
        function closeTab(e, path) {
            e.stopPropagation();
            const tabsContainer = document.getElementById('editor-tabs');
            const tab = document.querySelector(`.tab[data-path="${path}"]`);
            
            if (tab) {
                // If closing active file, clear editor or switch tab
                if (activeFile === path) {
                    if (isUnsaved) {
                        if (!confirm("Faylda saqlanmagan o'zgarishlar mavjud. Saqlamasdan yopishga aminmisiz?")) {
                            return;
                        }
                    }
                    isUnsaved = false;
                    tab.remove();
                    
                    const remainingTabs = document.querySelectorAll('.tab');
                    if (remainingTabs.length > 0) {
                        const nextTab = remainingTabs[remainingTabs.length - 1];
                        openFile(nextTab.dataset.path);
                    } else {
                        // Return to welcome screen
                        activeFile = null;
                        if (editor) {
                            editor.getModel().dispose();
                            editor = null;
                            document.getElementById('monaco-editor-instance').innerHTML = '';
                        }
                        document.getElementById('welcome-screen').style.display = 'flex';
                        document.getElementById('status-filepath').textContent = 'Tanlangan fayl yo\'q';
                        document.getElementById('status-language').textContent = 'Plain Text';
                        document.getElementById('status-savestate').textContent = '';
                    }
                } else {
                    tab.remove();
                }
            }
        }

        // 6. Run commands in backend console
        function runCommand(key) {
            const consoleOutput = document.getElementById('console-output');
            const loader = document.getElementById('console-loader');
            
            consoleOutput.textContent = "Buyruq ishga tushirilmoqda. Iltimos kutib turing...\n\n> " + key.toUpperCase();
            loader.style.display = 'block';

            fetch("{{ route('editor.api.run-command') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    command: key
                })
            })
            .then(res => res.json())
            .then(data => {
                loader.style.display = 'none';
                if (data.error) {
                    consoleOutput.textContent = "❌ Xatolik yuz berdi:\n" + data.error;
                } else {
                    consoleOutput.textContent = `$ ${data.command}\n\n${data.output}`;
                }
            })
            .catch(err => {
                loader.style.display = 'none';
                consoleOutput.textContent = "❌ Aloqa xatoligi yuz berdi.";
                console.error("Command error:", err);
            });
        }

        // Refresh File Explorer action
        document.getElementById('btn-refresh').addEventListener('click', () => {
            document.getElementById('file-tree').innerHTML = `
                <div style="display: flex; justify-content: center; align-items: center; height: 100%;">
                    <div class="spinner"></div>
                </div>
            `;
            loadFileTree();
        });
    </script>
</body>
</html>
