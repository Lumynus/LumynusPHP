<?php

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\Config;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Lumynus Core Inspector
 * Professional Static Analysis & Architecture Auditor
 */
final class Inspector
{
    private array $classesInitiated = [];
    private array $classesWithProblems = [];
    private array $architectureMap = [
        'namespaces' => [],
        'couplings'  => [],
        'scores'     => [],
        'metrics'    => [],
        'methods'    => [],
    ];

    public function __construct()
    {
        $this->scanSrc();
    }

    public function inspect(): void
    {
        foreach ($this->classesInitiated as $class) {
            $ref = new ReflectionClass($class);
            $className = $ref->getName();

            $this->analyzeDependencies($className);
            $this->analyzeNamespace($className, $ref->getNamespaceName());
            $this->analyzeNamingConventions($className, $ref->getMethods());

            $this->analyzeComplexityAndSize($className, $ref);
            $this->analyzeLogicRisks($className);

            $this->analyzeMethods($className, $ref);
        }

        $this->calculateScores();
    }

    /**
     * Scanner recursivo
     * Filtra classes para analisar apenas 'App\' e ignora 'Lumynus\'
     */
    private function scanSrc(): void
    {
        $path = Config::pathProject() . DIRECTORY_SEPARATOR . 'src';
        if (!is_dir($path)) return;

        $directory = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($directory);
        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        foreach ($phpFiles as $file) {
            require_once $file[0];
        }

        $allDefinitions = array_merge(
            get_declared_classes(),
            get_declared_traits(),
            get_declared_interfaces()
        );

        $allDefinitions = array_unique($allDefinitions);

        foreach ($allDefinitions as $def) {
            if (str_starts_with($def, 'Lumynus\\')) {
                continue;
            }

            if (str_starts_with($def, 'App\\')) {
                $this->classesInitiated[] = $def;
            }
        }
    }

    /**
     * Analisa métodos, removendo herdados do Framework
     */
    private function analyzeMethods(string $className, ReflectionClass $ref): void
    {
        $methodsData = [];

        foreach ($ref->getMethods() as $method) {

            $declaringClass = $method->getDeclaringClass()->getName();

            if (str_starts_with($declaringClass, 'Lumynus\\')) {
                continue;
            }

            $visibility = 'public';
            if ($method->isPrivate()) $visibility = 'private';
            elseif ($method->isProtected()) $visibility = 'protected';

            $params = [];
            foreach ($method->getParameters() as $param) {
                $type = $param->getType();
                $typeName = 'mixed';

                if ($type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();
                    if ($type->allowsNull()) $typeName = '?' . $typeName;
                } elseif ($type instanceof ReflectionUnionType) {
                    $typeName = implode('|', array_map(fn($t) => $t->getName(), $type->getTypes()));
                }

                $typeName = str_replace('Lumynus\\', '', $typeName);

                $params[] = [
                    'name' => '$' . $param->getName(),
                    'type' => $typeName,
                    'optional' => $param->isOptional()
                ];
            }

            $returnType = $method->getReturnType();
            $returnName = 'void';

            if ($method->getName() === '__construct' && !$returnType) {
                $returnName = '';
            } elseif ($returnType) {
                if ($returnType instanceof ReflectionNamedType) {
                    $returnName = $returnType->getName();
                    if ($returnType->allowsNull()) $returnName = '?' . $returnName;
                } elseif ($returnType instanceof ReflectionUnionType) {
                    $returnName = implode('|', array_map(fn($t) => $t->getName(), $returnType->getTypes()));
                }
            } elseif (!$returnType) {
                $returnName = 'mixed';
            }

            $returnName = str_replace('Lumynus\\Bundle\\Framework\\', '', $returnName);

            $methodsData[] = [
                'name' => $method->getName(),
                'visibility' => $visibility,
                'static' => $method->isStatic(),
                'abstract' => $method->isAbstract(),
                'params' => $params,
                'return' => $returnName,
            ];
        }

        $this->architectureMap['methods'][$className] = $methodsData;
    }

    private function analyzeDependencies(string $className): void
    {
        if (!class_exists($className) && !trait_exists($className) && !interface_exists($className)) return;

        $ref = new ReflectionClass($className);
        $file = $ref->getFileName();
        if (!$file) return;

        $tokens = token_get_all(file_get_contents($file));
        $dependencies = [];

        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (!is_array($token)) continue;

            [$id, $text] = $token;

            if (in_array($id, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                $k = $i + 1;
                while (isset($tokens[$k]) && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) {
                    $k++;
                }
                if (($tokens[$k][0] ?? null) === T_VARIABLE) {
                    $dependencies[] = ltrim($text, '\\');
                    continue;
                }
            }

            if ($id === T_USE) {
                $current = '';
                for ($j = $i + 1; $j < $count && $tokens[$j] !== ';'; $j++) {
                    if (is_array($tokens[$j])) $current .= $tokens[$j][1];
                }
                foreach (preg_split('/[\s,]+/', trim($current)) as $use) {
                    if ($use !== '') $dependencies[] = ltrim($use, '\\');
                }
            }

            if ($id === T_NEW) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if (!is_array($tokens[$j])) continue;
                    if (in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])) {
                        $dependencies[] = ltrim($tokens[$j][1], '\\');
                        break;
                    }
                    if ($tokens[$j][0] === T_VARIABLE) break;
                }
            }

            if ($id === T_DOUBLE_COLON) {
                $prev = $tokens[$i - 1] ?? null;
                if (is_array($prev) && in_array($prev[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])
                    && !in_array(strtolower($prev[1]), ['self', 'static', 'parent'])) {
                    $dependencies[] = ltrim($prev[1], '\\');
                }
            }

            if ($id === T_EXTENDS || $id === T_IMPLEMENTS) {
                $next = $tokens[$i + 2] ?? null;
                if (is_array($next) && in_array($next[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])) {
                    $dependencies[] = ltrim($next[1], '\\');
                }
            }
        }

        $cleanDeps = [];
        $seen = [];
        $scalars = ['string', 'int', 'float', 'bool', 'array', 'object', 'callable', 'iterable', 'mixed', 'void', 'never', 'null'];

        foreach ($dependencies as $dep) {
            $depLower = strtolower($dep);
            if (!is_string($dep) || stripos($dep, 'Lumynus') !== false || in_array($depLower, $scalars)) {
                continue;
            }
            $parts = explode('\\', $dep);
            $name = end($parts);
            $aliasKey = $name;
            if (stripos($name, ' as ') !== false) {
                [$namePart, $aliasPart] = array_map('trim', explode(' as ', $name));
                $aliasKey = $aliasPart ?: $namePart;
            }
            if (!isset($seen[$aliasKey])) {
                $cleanDeps[] = $dep;
                $seen[$aliasKey] = true;
            }
        }

        $shortName = $ref->getShortName();
        $this->architectureMap['couplings'][$className] = array_values(
            array_filter($cleanDeps, fn($d) => $d !== $shortName && $d !== $className)
        );
    }

    private function analyzeComplexityAndSize(string $className, ReflectionClass $ref): void
    {
        $file = $ref->getFileName();
        $content = file_get_contents($file);
        $lines = explode("\n", $content);

        $this->architectureMap['metrics'][$className]['loc'] = count($lines);

        $complexity = 1;
        $tokens = token_get_all($content);
        foreach ($tokens as $t) {
            if (is_array($t) && in_array($t[0], [T_IF, T_FOREACH, T_FOR, T_WHILE, T_CASE, T_BOOLEAN_AND, T_BOOLEAN_OR])) {
                $complexity++;
            }
        }
        $this->architectureMap['metrics'][$className]['complexity'] = $complexity;

        if ($complexity > 10) $this->classesWithProblems[$className][] = "High complexity ($complexity). Consider breaking down methods.";
        if (count($lines) > 250) $this->classesWithProblems[$className][] = "Large class size (" . count($lines) . " LOC). Potential God Object.";
    }

    private function analyzeNamingConventions(string $className, array $methods): void
    {
        foreach ($methods as $m) {
            if ($m->getDeclaringClass()->getName() !== $className) continue;
            if (!preg_match('/^(__)?[a-z][a-zA-Z0-9]*$/', $m->getName())) {
                $this->classesWithProblems[$className][] = "Method '{$m->getName()}' should be in camelCase.";
            }
        }
    }

    private function analyzeNamespace(string $className, string $ns): void
    {
        if (!str_starts_with($ns, 'App\\')) {
            $this->classesWithProblems[$className][] = "Namespace must start with 'App\\'.";
        }
    }

    private function analyzeLogicRisks(string $className): void
    {
        $ref = new ReflectionClass($className);
        $content = file_get_contents($ref->getFileName());

        if (str_contains($content, 'eval(')) $this->classesWithProblems[$className][] = "Security Risk: eval() usage detected.";
        if (str_contains($content, 'die(') || str_contains($content, 'exit;')) $this->classesWithProblems[$className][] = "Flow Risk: die/exit detected. Use Framework Responses.";
    }

    private function calculateScores(): void
    {
        foreach ($this->classesInitiated as $class) {
            $base = 100;
            $probs = count($this->classesWithProblems[$class] ?? []);
            $deps = count($this->architectureMap['couplings'][$class] ?? []);

            $score = $base - ($probs * 15) - ($deps * 2);
            $this->architectureMap['scores'][$class] = max($score, 0);
        }
    }

    public function renderInspectorHtml(): void
    {
        $grouped = [];
        $totalIssues = 0;
        foreach ($this->classesInitiated as $class) {
            $parts = explode('\\', $class);
            $name = array_pop($parts);
            $ns = implode('\\', $parts);
            $probs = $this->classesWithProblems[$class] ?? [];
            $totalIssues += count($probs);

            $ref = new ReflectionClass($class);
            $typeLabel = 'Class';
            if ($ref->isTrait()) $typeLabel = 'Trait';
            if ($ref->isInterface()) $typeLabel = 'Interface';

            $grouped[$ns][$class] = [
                'name' => $name,
                'type' => $typeLabel,
                'score' => $this->architectureMap['scores'][$class],
                'problems' => $probs,
                'couplings' => $this->architectureMap['couplings'][$class] ?? [],
                'metrics' => $this->architectureMap['metrics'][$class],
                'methods' => $this->architectureMap['methods'][$class] ?? []
            ];
        }
        ksort($grouped);

        $avgScore = count($this->classesInitiated) > 0 ? array_sum($this->architectureMap['scores']) / count($this->classesInitiated) : 100;

        echo $this->getTemplate(count($this->classesInitiated), $totalIssues, round($avgScore), $grouped);
    }

    private function getTemplate($totalClasses, $totalIssues, $avgScore, $grouped): string
    {
        return '<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Lumynus | Core Inspector</title>
    <style>' . $this->getStyles() . '</style>
</head>
<body>
    <div class="error-container">
        <header class="error-header">
            <button class="theme-toggle" onclick="toggleTheme()">
                <span id="theme-icon">🌙</span> <span id="theme-text">Dark Mode</span>
            </button>
            <div class="framework-name">Lumynus Framework</div>
            <h1 class="error-title">Core Inspector</h1>
            <p class="error-message">Static analysis and architectural health dashboard.</p>
        </header>

        <div class="error-cards">
            <div class="card"><div class="card-header">Total Files</div><div class="stat-val">' . $totalClasses . '</div></div>
            <div class="card"><div class="card-header">Global Health</div><div class="stat-val">' . $avgScore . '%</div></div>
            <div class="card"><div class="card-header">Open Issues</div><div class="stat-val ' . ($totalIssues > 0 ? 'text-error' : '') . '">' . $totalIssues . '</div></div>
        </div>

        <div class="main-layout">
            <aside class="sidebar card">
                <div class="card-header">Project Explorer</div>
                <div class="sidebar-search">
                    <input type="text" placeholder="Filter classes..." onkeyup="filterTree(this.value)">
                </div>
                <div class="tree-root">
                    ' . $this->renderSidebarTree($grouped) . '
                </div>
            </aside>

            <main class="inspector-content">
                <div id="welcome-screen" class="card empty-msg">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🔎</div>
                    <h3>Static Analysis Ready</h3>
                    <p>Select a file from the tree to audit.</p>
                </div>
                ' . $this->renderDetails($grouped) . '
            </main>
        </div>
    </div>
    <script>' . $this->getScripts() . '</script>
</body>
</html>';
    }

    private function renderSidebarTree(array $grouped): string
    {
        $html = '';
        foreach ($grouped as $ns => $classes) {
            $id = 'ns-' . md5($ns);
            $html .= '<div class="tree-ns">
                <div class="ns-folder" onclick="toggleNS(\'' . $id . '\')">
                    <span class="folder-arrow">▶</span>
                    <span class="folder-name">' . $ns . '</span>
                    <span class="folder-badge">' . count($classes) . '</span>
                </div>
                <div id="' . $id . '" class="ns-content" style="display:none;">';
            foreach ($classes as $full => $data) {
                $status = empty($data['problems']) ? 'dot-success' : 'dot-error';
                $icon = match($data['type']) {
                    'Trait' => 'T',
                    'Interface' => 'I',
                    default => 'C'
                };
                $badgeType = '<span class="type-badge type-'.$data['type'].'">'.$icon.'</span>';
                $html .= '<div class="class-node" onclick="showDetail(\'' . md5($full) . '\', this)" data-search="' . strtolower($data['name']) . '">
                    ' . $badgeType . ' ' . $data['name'] . ' <span class="status-dot ' . $status . '" style="margin-left:auto"></span>
                </div>';
            }
            $html .= '</div></div>';
        }
        return $html;
    }

    private function renderDetails(array $grouped): string
    {
        $html = '';
        foreach ($grouped as $ns => $classes) {
            foreach ($classes as $full => $data) {
                $id = md5($full);
                $html .= '<div id="detail-' . $id . '" class="detail-pane" style="display:none;">
                    <div class="card detail-header">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div class="framework-name">' . $data['type'] . ' in ' . $ns . '</div>
                                <h2 style="font-size: 2rem; margin:0; color:var(--accent-color);">' . $data['name'] . '</h2>
                            </div>
                            <div class="score-circle">' . $data['score'] . '%</div>
                        </div>
                    </div>

                    <div class="detail-grid">
                        <div class="card">
                            <div class="card-header">Metrics</div>
                            <div class="metric-item"><span>Complexity</span> <span class="badge">' . $data['metrics']['complexity'] . '</span></div>
                            <div class="metric-item"><span>Size (LOC)</span> <span class="badge">' . $data['metrics']['loc'] . '</span></div>
                            <div class="metric-item"><span>Couplings</span> <span class="badge">' . count($data['couplings']) . '</span></div>
                        </div>

                        <div class="card">
                            <div class="card-header">Issues & Violations</div>
                            <div style="padding: 1rem;">';
                if (empty($data['problems'])) {
                    $html .= '<div class="alert alert-success">✓ Clean code. No architectural issues found.</div>';
                } else {
                    foreach ($data['problems'] as $p) {
                        $html .= '<div class="alert alert-error">⚠ ' . $p . '</div>';
                    }
                }
                $html .= '</div></div>
                    </div>

                    <div class="card" style="margin-top:1.5rem;">
                        <div class="card-header">Methods & Signatures (' . count($data['methods']) . ')</div>
                        <div style="overflow-x:auto;">
                            <table class="methods-table">
                                <thead>
                                    <tr>
                                        <th width="100">Vis.</th>
                                        <th>Signature</th>
                                        <th>Return</th>
                                    </tr>
                                </thead>
                                <tbody>';

                if (empty($data['methods'])) {
                     $html .= '<tr><td colspan="3" style="text-align:center; padding:1rem; color:var(--text-muted)">No unique methods found (inherited from framework).</td></tr>';
                } else {
                    foreach ($data['methods'] as $m) {
                        $visibilityBadge = '<span class="badge-vis ' . $m['visibility'] . '">' . $m['visibility'] . '</span>';
                        if ($m['static']) $visibilityBadge .= ' <span class="badge-static">static</span>';
                        if ($m['abstract']) $visibilityBadge .= ' <span class="badge-static">abs</span>';

                        $paramsHtml = [];
                        foreach ($m['params'] as $p) {
                            $pStr = '<span class="type-hint">' . $p['type'] . '</span> ' . $p['name'];
                            if ($p['optional']) $pStr .= ' <span class="opt">?</span>';
                            $paramsHtml[] = $pStr;
                        }

                        $html .= '<tr>
                            <td>' . $visibilityBadge . '</td>
                            <td><span class="method-name">' . $m['name'] . '</span>( ' . implode(', ', $paramsHtml) . ' )</td>
                            <td><span class="type-hint">' . $m['return'] . '</span></td>
                        </tr>';
                    }
                }

                $html .= '      </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card" style="margin-top:1.5rem;">
                        <div class="card-header">Dependency Map (Couplings)</div>
                        <div class="coupling-tags">';

                if (empty($data['couplings'])) {
                    $html .= '<em style="color:var(--text-muted); font-size: 0.8rem; padding: 1rem; display:block;">No external couplings detected.</em>';
                } else {
                    foreach ($data['couplings'] as $c) {
                        $html .= '<span class="tag">' . $c . '</span>';
                    }
                }

                $html .= '</div></div>
        </div>';
            }
        }
        return $html;
    }

    private function getStyles(): string
    {
        return '
            * { margin:0; padding:0; box-sizing:border-box; transition: all 0.2s ease; }
            :root {
                --bg-primary: #ffffff; --bg-secondary: #f8fafc; --bg-tertiary: #e2e8f0;
                --text-primary: #1e293b; --text-secondary: #475569; --text-muted: #64748b;
                --border-color: #cbd5e1; --error-color: #dc2626; --accent-color: #3b82f6; --success-color: #10b981;
                --shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            }
            [data-theme="dark"] {
                --bg-primary: #0f172a; --bg-secondary: #1e293b; --bg-tertiary: #334155;
                --text-primary: #f1f5f9; --text-secondary: #cbd5e1; --text-muted: #94a3b8;
                --border-color: #334155; --error-color: #ef4444; --accent-color: #60a5fa;
            }
            body { font-family: ui-sans-serif, system-ui, sans-serif; background: var(--bg-primary); color: var(--text-primary); padding: 2rem; line-height: 1.5; }
            .error-container { max-width: 1400px; margin: 0 auto; display: flex; flex-direction: column; gap: 2rem; }
            .error-header { background: var(--bg-secondary); border: 2px solid var(--border-color); border-radius: 1rem; padding: 2.5rem; text-align: center; position: relative; box-shadow: var(--shadow); }
            .theme-toggle { position: absolute; top: 1.5rem; right: 1.5rem; padding: 0.6rem 1.2rem; border-radius: 0.8rem; border: 2px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); cursor: pointer; font-weight: 600; }
            .framework-name { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.1em; margin-bottom: 0.5rem; }
            .error-title { font-size: 2.5rem; font-weight: 800; color: var(--accent-color); margin-bottom: 0.5rem; }
            .error-message { color: var(--text-secondary); font-size: 1.1rem; }

            .error-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
            .card { background: var(--bg-secondary); border: 2px solid var(--border-color); border-radius: 1rem; overflow: hidden; box-shadow: var(--shadow); }
            .card-header { padding: 0.8rem 1.2rem; background: var(--bg-tertiary); font-weight: 700; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid var(--border-color); }
            .stat-val { padding: 1.5rem; font-size: 2.5rem; font-weight: 800; text-align: center; }
            .text-error { color: var(--error-color); }

            .main-layout { display: grid; grid-template-columns: 350px 1fr; gap: 2rem; height: 700px; }
            .sidebar { display: flex; flex-direction: column; }
            .sidebar-search { padding: 1rem; border-bottom: 1px solid var(--border-color); }
            .sidebar-search input { width: 100%; padding: 0.6rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); }
            .tree-root { flex: 1; overflow-y: auto; padding: 1rem; }

            .ns-folder { display: flex; align-items: center; padding: 0.6rem; cursor: pointer; border-radius: 0.5rem; gap: 0.5rem; margin-bottom: 0.2rem; }
            .ns-folder:hover { background: var(--bg-tertiary); }
            .folder-name { flex: 1; font-weight: 700; font-size: 0.85rem; }
            .folder-badge { font-size: 0.7rem; background: var(--bg-tertiary); padding: 0.1rem 0.5rem; border-radius: 1rem; font-weight: 800; }
            .ns-content { margin-left: 1.2rem; border-left: 1px solid var(--border-color); padding-left: 0.5rem; }
            .class-node { padding: 0.5rem 0.8rem; cursor: pointer; border-radius: 0.4rem; font-size: 0.85rem; display: flex; align-items: center; gap: 0.6rem; }
            .class-node:hover { background: var(--bg-tertiary); }
            .class-node.active { background: var(--accent-color); color: white; }
            .status-dot { width: 8px; height: 8px; border-radius: 50%; }
            .dot-success { background: var(--success-color); }
            .dot-error { background: var(--error-color); }

            .type-badge { font-size: 0.6rem; font-weight: bold; padding: 2px 5px; border-radius: 4px; color: #fff; width: 20px; text-align: center; }
            .type-Trait { background: #8b5cf6; }
            .type-Interface { background: #f59e0b; }
            .type-Class { background: #3b82f6; }

            .inspector-content { overflow-y: auto; }
            .empty-msg { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: var(--text-muted); }
            .detail-header { padding: 2rem; margin-bottom: 1.5rem; }
            .score-circle { width: 60px; height: 60px; border-radius: 50%; border: 4px solid var(--accent-color); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem; }
            .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
            .metric-item { display: flex; justify-content: space-between; padding: 1rem 1.2rem; border-bottom: 1px solid var(--border-color); }
            .badge { background: var(--bg-tertiary); padding: 0.2rem 0.6rem; border-radius: 0.4rem; font-family: monospace; font-weight: 600; }
            .alert { padding: 1rem; border-radius: 0.6rem; margin-bottom: 0.8rem; font-size: 0.9rem; font-weight: 600; border-left: 5px solid; }
            .alert-error { background: rgba(239, 68, 68, 0.1); color: var(--error-color); border-color: var(--error-color); }
            .alert-success { background: rgba(16, 185, 129, 0.1); color: var(--success-color); border-color: var(--success-color); }
            .coupling-tags { padding: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.6rem; }
            .tag { background: var(--bg-tertiary); padding: 0.4rem 0.8rem; border-radius: 0.5rem; font-size: 0.8rem; border: 1px solid var(--border-color); }

            .methods-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
            .methods-table th { text-align: left; padding: 0.8rem 1rem; border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; }
            .methods-table td { padding: 0.8rem 1rem; border-bottom: 1px solid var(--border-color); }
            .methods-table tr:last-child td { border-bottom: none; }
            .method-name { font-family: monospace; font-weight: 700; color: var(--accent-color); }
            .type-hint { color: var(--text-muted); font-family: monospace; font-size: 0.85rem; }
            .opt { color: var(--error-color); font-weight: bold; cursor: help; }

            .badge-vis { font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; font-weight: 700; }
            .public { background: rgba(16, 185, 129, 0.2); color: var(--success-color); }
            .protected { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
            .private { background: rgba(239, 68, 68, 0.2); color: var(--error-color); }
            .badge-static { font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; background: var(--bg-tertiary); color: var(--text-muted); margin-left: 4px; }
        ';
    }

    private function getScripts(): string
    {
        return '
            function toggleTheme() {
                const html = document.documentElement;
                const isDark = html.getAttribute("data-theme") === "dark";
                html.setAttribute("data-theme", isDark ? "light" : "dark");
                document.getElementById("theme-icon").textContent = isDark ? "🌙" : "☀️";
                localStorage.setItem("lumynus_theme", isDark ? "light" : "dark");
            }
            function toggleNS(id) {
                const el = document.getElementById(id);
                const arrow = el.previousElementSibling.querySelector(".folder-arrow");
                const isOpen = el.style.display !== "none";
                el.style.display = isOpen ? "none" : "block";
                arrow.style.transform = isOpen ? "rotate(0deg)" : "rotate(90deg)";
            }
            function showDetail(id, el) {
                document.getElementById("welcome-screen").style.display = "none";
                document.querySelectorAll(".detail-pane").forEach(p => p.style.display = "none");
                document.querySelectorAll(".class-node").forEach(n => n.classList.remove("active"));
                document.getElementById("detail-" + id).style.display = "block";
                el.classList.add("active");
            }
            function filterTree(val) {
                const query = val.toLowerCase();
                document.querySelectorAll(".class-node").forEach(node => {
                    node.style.display = node.getAttribute("data-search").includes(query) ? "flex" : "none";
                });
            }
            window.onload = () => {
                const theme = localStorage.getItem("lumynus_theme") || "dark";
                document.documentElement.setAttribute("data-theme", theme);
                document.getElementById("theme-icon").textContent = theme === "dark" ? "🌙" : "☀️";
            };
        ';
    }
}
