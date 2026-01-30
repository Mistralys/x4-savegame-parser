# Project Manifest

This directory contains the "Source of Truth" for the 
X4 Savegame Monitor & Viewer. These documents are designed 
to provide AI agents and developers with a comprehensive 
understanding of the project without requiring a full code 
audit.

---

## Quick Navigation

- **New to the project?** Start with [Tech Stack & Patterns](./01-tech-stack-and-patterns.md)
- **Need API reference?** See [Public API Reference](./03-public-api-reference.md)
- **Understanding data flow?** Read [Data Flows](./04-data-flows.md)
- **Working with constraints?** Check [Constraints & Rules](./05-constraints-and-rules.md)

---

## Core Documents

### 1. [Tech Stack & Patterns](./01-tech-stack-and-patterns.md)
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

### 2. [File Tree](./02-file-tree.md)
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

### 3. [Public API Reference](./03-public-api-reference.md)
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

### 4. [Data Flows](./04-data-flows.md)
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

### 5. [Constraints & Rules](./05-constraints-and-rules.md)
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

### 6. [NDJSON Interface](./ndjson-interface.md)
**Purpose**: Technical specification for the Monitor's machine-readable output protocol.

**Contents**:
- NDJSON protocol specification
- Message schema and types (event, tick, log, error)
- Event catalog with payloads
- Usage examples for launcher applications

**When to read**: When building tools that consume monitor output or implementing launchers.

---

### 7. [CLI API Reference](./07-cli-api-reference.md)
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

## Document Relationships

```
Start Here
    ↓
[01-tech-stack-and-patterns.md] ← Overview of "what" and "why"
    ↓
[02-file-tree.md] ← "Where" is everything located
    ↓
[03-public-api-reference.md] ← "What" methods are available
    ↓
[04-data-flows.md] ← "How" does data move through the system
    ↓
[05-constraints-and-rules.md] ← "Why" are things done this way
    ↓
    ├─→ [ndjson-interface.md] ← Specialized: Monitor protocol
    └─→ [07-cli-api-reference.md] ← Specialized: CLI Query API
```

---

## How to Use This Manifest

### For AI Agents

1. **Initial Context Gathering**: Read documents 1-2 for project overview
2. **Implementation Tasks**: Reference document 3 for API signatures
3. **Understanding Flows**: Use document 4 to trace data paths
4. **Constraint Validation**: Check document 5 before making changes
5. **Specialized Features**: 
   - Refer to document 6 (NDJSON) for monitor integration
   - Refer to document 7 (CLI API) for query interface integration

### For Human Developers

1. **Onboarding**: Read documents in order (1 → 2 → 3 → 4 → 5)
2. **Quick Reference**: Bookmark document 3 for daily API lookups
3. **Debugging**: Use document 4 to understand data transformations
4. **Architecture Decisions**: Consult document 5 before major changes
5. **Integration**: 
   - Read document 6 (NDJSON) when building monitor consumers
   - Read document 7 (CLI API) when building query-based applications

### For Documentation Updates

When code changes affect the manifest:

1. **New Classes**: Update document 3 (API Reference)
2. **New Features**: Update document 4 (Data Flows) if data processing changes
3. **Architectural Changes**: Update document 5 (Constraints) and explain rationale
4. **Directory Changes**: Update document 2 (File Tree)
5. **Dependency Changes**: Update document 1 (Tech Stack)

---

## Maintenance Guidelines

### Keep Documents in Sync

- Update manifest when making architectural changes
- Document new patterns immediately
- Remove deprecated information promptly
- Cross-reference between documents when appropriate

### Signature-Only Rule

- Document 3 (API Reference) must remain **signatures only**
- No implementation details or code logic
- Focus on "what" methods do, not "how" they do it
- Use PHPDoc for parameter/return type details

### Clarity Over Brevity

- Err on the side of more explanation
- Use examples liberally in documents 4 and 5
- Maintain clear section hierarchy
- Keep navigation links updated

---

## Version History

This manifest was created on **2026-01-29** as a comprehensive "Source of Truth" for the X4 Savegame Parser project. It represents the state of the codebase at that time.

**Major updates should be noted here**:
- 2026-01-29: Initial manifest creation (documents 1-6)
- 2026-01-30: Added CLI API implementation and documentation (document 7)
  - New CLI query interface with JMESPath filtering
  - Query caching system for pagination
  - Standard JSON response envelope
  - 19 query commands covering all savegame data
  - Comprehensive integration guide for Rust/Tauri applications

---

## Questions or Issues?

If you find inconsistencies between the manifest and the actual codebase:

1. Verify the codebase is the authoritative source
2. Update the manifest to reflect reality
3. Document the reason for any deviations
4. Consider if a constraint needs updating

The manifest serves the code, not the other way around.
