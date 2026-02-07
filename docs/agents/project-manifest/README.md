# Project Manifest

This directory contains the "Source of Truth" for the 
X4 Savegame Monitor & Viewer. These documents are designed 
to provide AI agents and developers with a comprehensive 
understanding of the project without requiring a full code 
audit.

---

## Quick Navigation

- **New to the project?** Start with [Tech Stack & Patterns](./tech-stack-and-patterns.md)
- **Need API reference?** See [Public API Reference](./public-api-reference.md)
- **Understanding data flow?** Read [Data Flows](./data-flows.md)
- **Working with constraints?** Check [Constraints & Rules](./constraints-and-rules.md)

---

## Core Documents

### [Tech Stack & Patterns](./tech-stack-and-patterns.md)
**Purpose**: Overview of runtime environment, dependencies, and architectural patterns.

**Contents**:
- PHP 8.4+ runtime environment and required extensions
- Core dependencies (ReactPHP, mistralys/x4-core, league/climate)
- Architectural patterns (Collections, Fragment-based parsing, Type system)
- Storage strategy (JSON-centric)
- Execution modes (CLI, UI Server, Monitor)
- Key design decisions

**When to read**: First document to understand the technical foundation.

---

### [File Tree](./file-tree.md)
**Purpose**: Visual directory structure of the entire project.

**Contents**:
- Complete project directory hierarchy
- Purpose of each major directory
- Location of key files (entry points, configs)
- Source code organization (`src/X4/SaveViewer/`)
- Test structure (`tests/`)
- Vendor dependencies structure
- Generated/runtime directories

**When to read**: To navigate the codebase or locate specific files.

---

### [Public API Reference](./public-api-reference.md)
**Purpose**: Comprehensive signature-only reference for all public classes and methods.

**Contents**:
- **Core Services**: SaveManager, SaveParser, SaveReader, Config
- **Collections System**: Collections hub and 10 typed collections
- **Type Models**: BaseComponentType and all game entity types
- **Data Readers**: Blueprints, Factions, Statistics, Log, etc.
- **Monitor System**: X4Monitor, X4Server, output interfaces
- **UI System**: Page hierarchy, navigation structure
- **Parser Components**: Fragment parsers, Tag parsers
- **Interfaces & Traits**: Component interfaces, utility traits

**Format**: Method signatures with PHPDoc annotations, **no implementations**.

**When to read**: When you need to know "what methods are available" without implementation details.

---

### [Data Flows](./data-flows.md)
**Purpose**: Detailed description of how data moves through the system.

**Contents**:
- **CLI Extraction Flow**: Step-by-step from .gz to JSON
- **Monitor Flow**: Auto-extraction with event loop and NDJSON events
- **UI Server & Request Flow**: HTTP request handling and page rendering
- **Construction Plans Flow**: Parsing construction plans XML
- **Save File Discovery Flow**: Finding and cataloging saves
- **Data Processing Flow**: Post-parse derived data generation
- **Backup Creation Flow**: Creating savegame backups

**Format**: Structured diagrams using code blocks and text descriptions.

**When to read**: To understand "how does X work" or debug data processing issues.

---

### [Constraints & Rules](./constraints-and-rules.md)
**Purpose**: Established architectural constraints, conventions, and rules.

**Contents**:
- Language & type system requirements (PHP 8.4+, strict typing)
- File I/O constraints (synchronous only)
- XML parsing strategy (two-stage approach)
- Data storage rules (JSON-centric, no database)
- Collections system constraints (singleton per parse)
- UI architecture requirements (x4-core inheritance)
- Monitor system rules (ReactPHP, CLI-only)
- Configuration management (singleton pattern)
- Error handling conventions
- Code style guidelines
- Performance guidelines

**When to read**: Before making architectural changes or understanding "why it works this way".

---

## Specialized Documents

### [NDJSON Interface](./ndjson-interface.md)
**Purpose**: Technical specification for the Monitor's machine-readable output protocol.

**Contents**:
- NDJSON protocol specification
- Message schema and types (event, tick, log, error)
- Event catalog with payloads
- Usage examples for launcher applications

**When to read**: When building tools that consume monitor output or implementing launchers.

---

### [CLI API Reference](./cli-api-reference.md)
**Purpose**: Complete reference for the CLI query API and JMESPath filtering.

**Contents**:
- Standard response envelope specification
- All query commands with input/output schemas
- JMESPath feature subset and filtering examples
- Pagination and caching workflows
- Error codes and handling
- Integration guide for external applications (Rust/Tauri)

**When to read**: When building applications that query savegame data or implementing launchers.

---

### [Extracted Save Location](./extracted-save-location.md)
**Purpose**: Comprehensive guide to where extracted savegame data is stored.

**Contents**:
- Default storage location: `{gameFolder}/archived-saves/`
- Custom storage configuration via `storageFolder` config key
- Folder naming conventions: `unpack-{datetime}-{savename}`
- Directory structure details (JSON/, XML/, .cache/, analysis.json, backup.gz)
- Methods for finding extracted data
- Common issues and troubleshooting
- Configuration reference for storage-related settings

**When to read**: When setting up the project, troubleshooting extraction issues, or customizing storage locations.

## Document Relationships

```
Start Here
    ↓
[tech-stack-and-patterns.md] ← Overview of "what" and "why"
    ↓
[file-tree.md] ← "Where" is everything located
    ↓
[public-api-reference.md] ← "What" methods are available
    ↓
[data-flows.md] ← "How" does data move through the system
    ↓
[constraints-and-rules.md] ← "Why" are things done this way
    ↓
    ├─→ [ndjson-interface.md] ← Specialized: Monitor protocol
    ├─→ [cli-api-reference.md] ← Specialized: CLI Query API
    └─→ [extracted-save-location.md] ← Specialized: Storage configuration
```

---

## How to Use This Manifest

### For AI Agents

1. **Initial Context Gathering**: Read Tech Stack & Patterns and File Tree for project overview
2. **Implementation Tasks**: Reference Public API Reference for API signatures
3. **Understanding Flows**: Use Data Flows to trace data paths
4. **Constraint Validation**: Check Constraints & Rules before making changes
5. **Specialized Features**: 
   - Refer to NDJSON Interface for monitor integration
   - Refer to CLI API Reference for query interface integration
   - Refer to Extracted Save Location for extraction and storage configuration

### For Documentation Updates

When code changes affect the manifest:

1. **New Classes**: Update Public API Reference
2. **New Features**: Update Data Flows if data processing changes
3. **Architectural Changes**: Update Constraints & Rules and explain rationale
4. **Directory Changes**: Update File Tree
5. **Dependency Changes**: Update Tech Stack

---

## Maintenance Guidelines

### Keep Documents in Sync

- Update manifest when making architectural changes
- Document new patterns immediately
- Remove deprecated information promptly
- Cross-reference between documents when appropriate

### Signature-Only Rule

- Public API Reference must remain **signatures only**
- No implementation details or code logic
- Focus on "what" methods do, not "how" they do it
- Use PHPDoc for parameter/return type details

### Clarity Over Brevity

- Err on the side of more explanation
- Use examples liberally in Data Flows and Constraints & Rules
- Maintain clear section hierarchy
- Keep navigation links updated

---

## Single-File Version (NotebookLM)

For tools like NotebookLM that work better with single files, a merged version of all manifest documents is available:

**File**: `X4-Savegame-Parser-Manifest.md` (in the docs directory)

**To regenerate**:
```bash
# Windows
bin\merge-manifest.bat

# Unix/Linux/Mac
bin/merge-manifest
```

**How it works**:
- Automatically discovers all `.md` files in the project-manifest directory
- README.md is always placed first
- Other documents follow in alphabetical order
- No manual maintenance needed when adding or renaming files

**Transformations applied**:
- All headers increased by one level (add one `#`)
- File links converted to anchor links (e.g., `[doc](./file.md)` → `[doc](#file)`)
- Each document gets an anchor ID for navigation

---

## Questions or Issues?

If you find inconsistencies between the manifest and the actual codebase:

1. Verify the codebase is the authoritative source
2. Update the manifest to reflect reality
3. Document the reason for any deviations
4. Consider if a constraint needs updating

The manifest serves the code, not the other way around.
