# Agent Guide

## Folder For Saving Plans

- **Folder location**: Save all plans you create in [plans](/docs/agents/plans).
- **File naming**: Use lowercase file names with hyphens as separators.

## Project Manifest

You have access to a comprehensive technical documentation
of the project, which is the "Source of Truth" for the 
X4 Savegame Monitor & Viewer. It provides a comprehensive 
understanding of the project without requiring a full code 
audit.

[README](docs/agents/project-manifest/README.md)

**Always use the project manifest whenever you need to know
something about the project.**

## After Implementing Features or Changes

After implementing a new feature or changes, always check
if the Project Manifest needs to be updated, and add the
necessary details automatically.

## PHPStan Usage

PHPStan is available in the project. If you must run the
tool, **the maximum level to use is 6**. Higher levels will 
generate too many messages. 
