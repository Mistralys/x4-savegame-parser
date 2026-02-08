# Agent Operating System: X4 Savegame Parser

> **Mission Critical**: This document defines how AI agents interact with the X4 Savegame Manager & Viewer codebase. Following this protocol ensures architectural integrity, token efficiency, and high-quality contributions.

---

## 📚 1. PROJECT MANIFEST - START HERE

### Core Philosophy: Manifest-First Development

**The Project Manifest is the authoritative source of truth.** If the manifest conflicts with the code:
1. The manifest represents the intended architecture
2. The code may contain bugs or technical debt
3. Flag the discrepancy and propose corrections

### 🎯 Manifest Location

**Primary**: `docs/agents/project-manifest/`  
**Merged Version**: `docs/X4-Savegame-Parser-Manifest.md` (for NotebookLM)

### 📖 Core Manifest Documents

| Document | Purpose | Read When |
|----------|---------|-----------|
| **[README](docs/agents/project-manifest/README.md)** | Navigation hub and document relationships | Start of every session |
| **[Tech Stack & Patterns](docs/agents/project-manifest/tech-stack-and-patterns.md)** | PHP 8.4+, ReactPHP, mistralys libs, architectural patterns | Understanding "what" and "why" |
| **[File Tree](docs/agents/project-manifest/file-tree.md)** | Complete directory structure with purpose annotations | Locating files or understanding organization |
| **[Public API Reference](docs/agents/project-manifest/public-api-reference.md)** | Signatures-only for all public classes/methods | Finding available methods without reading source |
| **[Data Flows](docs/agents/project-manifest/data-flows.md)** | Step-by-step data movement diagrams | Understanding "how" features work |
| **[Constraints & Rules](docs/agents/project-manifest/constraints-and-rules.md)** | Architectural constraints and conventions | Before making changes or understanding "why" |

### 📋 Specialized Reference Documents

| Document | Purpose | Read When |
|----------|---------|-----------|
| **[NDJSON Interface](docs/agents/project-manifest/ndjson-interface.md)** | Monitor's machine-readable output protocol | Building monitor integrations |
| **[CLI API Reference](docs/agents/project-manifest/cli-api-reference.md)** | Query API specification with JMESPath filtering | Building CLI integrations or launchers |
| **[Extracted Save Location](docs/agents/project-manifest/extracted-save-location.md)** | Storage paths and configuration | Setup, storage issues, or path configuration |

---

## 🚀 2. QUICK START WORKFLOW

### Visual Ingestion Path for New Agents

```
┌─────────────────────────────────────────────────────────────┐
│ SESSION START: Read This AGENTS.md File                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 1: Project Context                                     │
│ → Read: docs/agents/project-manifest/README.md              │
│ → Time: 2-3 minutes                                         │
│ → Outcome: Understand document structure & relationships    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 2: Technical Foundation                                │
│ → Read: tech-stack-and-patterns.md                          │
│ → Time: 5-7 minutes                                         │
│ → Outcome: PHP 8.4+, ReactPHP, collections, type system     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 3: Architectural Constraints                           │
│ → Read: constraints-and-rules.md                            │
│ → Time: 5-7 minutes                                         │
│ → Outcome: Know what NOT to do (critical!)                  │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 4: Navigation Reference                                │
│ → Read: file-tree.md                                        │
│ → Time: 3-5 minutes                                         │
│ → Outcome: Mental map of codebase structure                 │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 5: API Surface                                         │
│ → Reference: public-api-reference.md (as needed)            │
│ → Time: On-demand lookup                                    │
│ → Outcome: Know available methods without reading source    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 6: Task-Specific Deep Dive                             │
│ → data-flows.md: Understand specific feature flow           │
│ → Specialized docs: CLI/NDJSON/Storage as needed            │
│ → Source code: Only after manifest consultation             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ BEGIN IMPLEMENTATION                                         │
└─────────────────────────────────────────────────────────────┘
```

### Execution Time Budget

- **Initial session setup**: 15-20 minutes (mandatory)
- **Subsequent sessions**: 2-5 minutes (refresh constraints & API)
- **ROI**: Saves 2-10 hours of code exploration and prevents architectural violations

---

## 📝 3. MANIFEST MAINTENANCE RULES

### Synchronization Matrix

When you make changes to the codebase, **you must update** the corresponding manifest documents. This table defines the mapping:

| Code Change Type | Manifest Documents to Update | Mandatory | Notes |
|------------------|------------------------------|-----------|-------|
| **New public class** | `public-api-reference.md`, `file-tree.md` | ✅ YES | Add full signature with PHPDoc |
| **New public method** | `public-api-reference.md` | ✅ YES | Signature only, no implementation |
| **New collection type** | `public-api-reference.md`, `constraints-and-rules.md` | ✅ YES | Follow singleton collection pattern |
| **New data reader** | `public-api-reference.md`, `data-flows.md` | ✅ YES | Document data extraction flow |
| **New execution mode** | `tech-stack-and-patterns.md`, `data-flows.md` | ✅ YES | CLI/Monitor/UI pattern |
| **New CLI command** | `cli-api-reference.md` | ✅ YES | Include input/output schema and examples |
| **New monitor event** | `ndjson-interface.md` | ✅ YES | Add to event catalog with payload schema |
| **New storage location** | `extracted-save-location.md` | ✅ YES | Document path structure and config |
| **Dependency added/removed** | `tech-stack-and-patterns.md` | ✅ YES | Update version and justify |
| **Architectural constraint** | `constraints-and-rules.md` | ✅ YES | Document rule and rationale |
| **Data flow modification** | `data-flows.md` | ✅ YES | Update affected flow diagrams |
| **Directory structure change** | `file-tree.md` | ✅ YES | Keep hierarchy accurate |
| **Private method change** | None | ❌ NO | Manifest is public API only |
| **Refactoring (no API change)** | None (unless patterns change) | ⚠️ MAYBE | Only if architectural pattern changes |
| **Bug fix (no API change)** | None | ❌ NO | Unless bug reveals constraint violation |

### Update Protocol

1. **During Implementation**: Update manifest documents in the same commit/session
2. **Signature-Only Rule**: Public API Reference contains **only** method signatures, types, and PHPDoc
3. **Implementation-Free Zone**: Never include "how" code works, only "what" it provides
4. **Cross-Reference**: Link between documents when concepts span multiple files

### Regenerating Merged Manifest

After updating individual manifest files, regenerate the merged version:

```bash
# Windows
bin\merge-manifest.bat

# Unix/Linux/Mac
bin/merge-manifest
```

This creates `docs/X4-Savegame-Parser-Manifest.md` for single-file reference tools.

---

## ⚡ 4. EFFICIENCY RULES - SEARCH SMART

### Token Conservation Protocol

**Purpose**: Minimize unnecessary file reads and searches. The manifest exists to prevent wasteful code exploration.

### Rule Hierarchy (Apply in Order)

#### 🥇 Priority 1: Check Manifest FIRST

| Task | Wrong Approach ❌ | Correct Approach ✅ |
|------|------------------|---------------------|
| "Where is the SaveManager class?" | Search filesystem for `SaveManager` | Check `file-tree.md` → `src/X4/SaveViewer/SaveManager.php` |
| "What methods does SaveParser have?" | Read `src/X4/SaveViewer/SaveParser.php` | Check `public-api-reference.md` → Parser section |
| "How does extraction work?" | Grep for "extract" and read multiple files | Read `data-flows.md` → CLI Extraction Flow |
| "Can I use async file I/O?" | Try to implement and test | Check `constraints-and-rules.md` → File I/O = synchronous only |
| "Where are extracted saves stored?" | Search for storage paths in code | Read `extracted-save-location.md` → Default: `{gameFolder}/archived-saves/` |
| "What CLI commands exist?" | Read `bin/` directory and source files | Read `cli-api-reference.md` → Command catalog |
| "What monitor events are available?" | Read ReactPHP monitor code | Read `ndjson-interface.md` → Event catalog |

#### 🥈 Priority 2: Use Targeted Search

If manifest doesn't answer your question:
1. **File Tree Known?** Use `read_file` with specific path
2. **Symbol Name Known?** Use `grep_search` with exact symbol
3. **Concept Unknown?** Use `semantic_search` with clear query

#### 🥉 Priority 3: Read Source Code

Only after manifest + targeted search:
- Read specific implementation files
- Trace execution paths
- Analyze complex logic

### Search Anti-Patterns (Never Do This)

❌ **Blind Filesystem Scanning**: "Let me recursively search all PHP files"  
❌ **Premature Source Reading**: Reading implementation before checking manifest  
❌ **Repeated Similar Searches**: Not remembering information from manifest  
❌ **Ignoring Constraints**: Implementing features that violate established rules  

### Efficiency Metrics

**Target**: 80% of questions answered by manifest alone  
**Acceptable**: 15% require targeted search + manifest  
**Minimize**: 5% require deep source code reading  

---

## 🚨 5. FAILURE PROTOCOL & DECISION MATRIX

### Decision Framework for Edge Cases

When you encounter ambiguity, missing documentation, or uncertainty, follow this matrix:

| Scenario | Diagnostic Questions | Action | Priority | Rationale |
|----------|---------------------|--------|----------|-----------|
| **Ambiguous Requirement** | "Can this be interpreted multiple ways?" | 1. Choose most restrictive interpretation<br>2. Flag ambiguity in response<br>3. Ask for clarification if impacts multiple files | 🔴 MUST | Conservative approach prevents over-engineering |
| **Manifest/Code Conflict** | "Does code contradict manifest?" | 1. Trust manifest as intended design<br>2. Flag code as potentially buggy<br>3. Propose fix aligned with manifest | 🔴 MUST | Manifest = source of truth |
| **Missing Manifest Entry** | "Should this be documented?" | 1. If public API → ⚠️ **Stop and update manifest first**<br>2. If private → Continue but note gap<br>3. If architectural → Update constraints.md | 🟡 SHOULD | Maintain documentation integrity |
| **Constraint Violation Temptation** | "Would breaking this rule be easier?" | 1. ⛔ **Do not violate**<br>2. Propose constraint change with strong rationale<br>3. Wait for approval before proceeding | 🔴 MUST | Constraints exist for reasons (often hard-learned) |
| **Untested Code Path** | "Does this have test coverage?" | 1. Check `tests/testsuites/` for relevant tests<br>2. If none exist → Implement tests first<br>3. Follow existing test patterns in `tests/classes/` | 🟡 SHOULD | Regression prevention |
| **Dependency Addition** | "Do I need a new library?" | 1. Check if mistralys/application-utils already provides it<br>2. Verify PHP 8.4+ native solution doesn't exist<br>3. If truly needed → Update tech-stack.md + composer.json | 🟡 SHOULD | Minimize dependency bloat |
| **Performance Concern** | "Will this be slow?" | 1. Check constraints.md → "Performance Guidelines"<br>2. Large savegames are 1+ GB → test implications<br>3. Fragment-based parsing is required for large files | 🟠 MAY | Savegames can be massive |
| **Async/Await Usage** | "Should I use ReactPHP promises here?" | 1. Check execution mode: CLI/UI = synchronous, Monitor = ReactPHP<br>2. Never mix paradigms within a mode<br>3. See constraints: "File I/O is always synchronous" | 🔴 MUST | Critical architectural boundary |
| **Type System Ambiguity** | "Should this be strictly typed?" | 1. PHP 8.4+ strict types = YES<br>2. All public methods must have type hints<br>3. Use PHPDoc for complex return types | 🔴 MUST | Project uses strict typing |
| **Configuration Option** | "Should this be configurable?" | 1. Check `config.dist.json` for precedent<br>2. Use Config singleton: `X4SaveViewer::getConfig()`<br>3. Document in extracted-save-location.md if storage-related | 🟡 SHOULD | Centralized configuration pattern |
| **XML Parsing Strategy** | "How should I parse this XML?" | 1. Check constraints: Two-stage approach (fragment → DOM)<br>2. Large files = XMLReader fragments first<br>3. Never load entire XML into memory | 🔴 MUST | Memory constraints for 1+ GB files |
| **Collection Access** | "How do I get game data?" | 1. Collections are singletons per parse<br>2. Access via SaveData→getCollections()<br>3. Collections return typed component objects | 🔴 MUST | Core architectural pattern |
| **Error Handling** | "What error handling style?" | 1. Check existing patterns in similar classes<br>2. Use exceptions for exceptional cases<br>3. Log errors via established logging system | 🟡 SHOULD | Consistency matters |
| **UI Page Addition** | "How do I add a new page?" | 1. Must extend x4-core page classes<br>2. Follow existing page hierarchy in file-tree.md<br>3. Register in navigation structure | 🔴 MUST | UI framework requirement |
| **PHPStan Errors** | "Can I ignore PHPStan warnings?" | 1. Maximum level used = 6 (never higher)<br>2. Fix legitimate issues<br>3. Use `@phpstan-ignore` only for false positives with comment | 🟡 SHOULD | Static analysis helps |

### Priority Definitions

- 🔴 **MUST**: Non-negotiable - violating this breaks architecture
- 🟡 **SHOULD**: Strong recommendation - skip only with documented reason
- 🟠 **MAY**: Discretionary - evaluate on case-by-case basis

### Escalation Protocol

If you cannot resolve a scenario using this matrix:

1. **Document**: Write detailed description of the ambiguity
2. **Research**: Read related manifest sections and source code
3. **Propose**: Suggest 2-3 options with trade-offs
4. **Flag**: Mark as requiring human decision
5. **Do Not**: Proceed with implementation until resolved

---

## 📊 PROJECT QUICK REFERENCE

### Project Identity

- **Name**: X4 Savegame Manager & Viewer
- **Purpose**: Parse, monitor, backup, and visualize X4: Foundations savegames
- **Language**: PHP 8.4+ (strict typing)
- **Architecture**: Event-driven (Monitor), MVC (UI), CLI (extraction)
- **Storage**: JSON-centric, no database
- **Scale**: Handles 1+ GB savegame files

### Tech Stack Snapshot

- **Runtime**: PHP 8.4+ with ZLIB, DOM, XMLReader extensions
- **Core Deps**: ReactPHP (async), mistralys/x4-core (data), league/climate (CLI)
- **Patterns**: Collections (singleton), Fragments (XML parsing), Type models
- **Testing**: PHPUnit, PHPStan (max level 6)

### Entry Points

| Mode | Script | Purpose |
|------|--------|---------|
| CLI Extract | `bin/extract` / `extract.bat` | Manual savegame extraction |
| CLI Query | `bin/query` / `query.bat` | JSON data querying with JMESPath |
| Monitor | `bin/run-monitor` / `run-monitor.bat` | Auto-backup daemon with NDJSON events |
| UI Server | `bin/run-ui` / `run-ui.bat` | Web-based viewer (ReactPHP server) |

### Key Directories

- `src/X4/SaveViewer/` - Core implementation
- `docs/agents/project-manifest/` - This manifest
- `tests/` - Test suites and fixtures
- `bin/` - Executable entry points
- `cache/` - Runtime cache (gitignored)

---

## 🎯 AGENT SUCCESS CHECKLIST

Before considering a task complete, verify:

- ✅ Manifest consulted before reading source code
- ✅ Changes aligned with constraints-and-rules.md
- ✅ Public API changes reflected in public-api-reference.md
- ✅ Data flow changes reflected in data-flows.md if applicable
- ✅ New files added to file-tree.md if applicable
- ✅ CLI commands documented in cli-api-reference.md if applicable
- ✅ Monitor events documented in ndjson-interface.md if applicable
- ✅ Storage changes documented in extracted-save-location.md if applicable
- ✅ Strict typing maintained (PHP 8.4+)
- ✅ No async/await in synchronous contexts
- ✅ No database dependencies introduced
- ✅ Test coverage for public API changes
- ✅ PHPStan level 6 compliance

---

## 📞 SUPPORT & QUESTIONS

### Documentation Issues

If you find manifest/code inconsistencies:
1. Verify current codebase state
2. Update manifest to match reality
3. Document reason for any deviations
4. Consider if a constraint needs revision

### Manifest Updates

When adding new content to the manifest:
- Follow existing document structure
- Use clear, hierarchical headings
- Include examples for complex concepts
- Cross-reference related sections
- Maintain signature-only rule in API reference

---

## Version Information

**Manifest Version**: This agents guide references manifest structure as of project manifest README v1.0  
**Last Updated**: By agent following implementation changes  
**Maintenance**: Auto-updated when architectural changes occur

---

**Remember**: The manifest exists to make you efficient. Use it, trust it, and keep it synchronized with reality.
