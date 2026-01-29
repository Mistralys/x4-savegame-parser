# Agent Guide

## Folder For Saving Plans

Save all plans you create in:

`/docs/agents/plans`

Use lowercase file names with hyphens as separators.

## Project Manifest

You have access to a comprehensive technical documentation
of the project, which is the "Source of Truth" for the 
X4 Savegame Monitor & Viewer. It provides a comprehensive 
understanding of the project without requiring a full code 
audit.

`docs/agents/project-manifest/README.md`

**Always use the project manifest whenever you need to know
something about the project.**

## After Implementing Features or Changes

Afterimplementing a new feature or changes, always check
if the Project Manifest needs to be updated, and add the
necessary details automatically.

## PHPStan Usage

PHPStan is available in the project. If you must run the
tool, **use level 6**. Higher levels will generate too
many messages.
