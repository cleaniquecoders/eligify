# Architecture

This section covers the architectural design and patterns used in Eligify.

## Overview

Eligify is built using modern Laravel package development practices and follows SOLID principles. The package is designed to be extensible, testable, and maintainable.

## Documentation in this Section

- **[Design Patterns](design-patterns.md)** - Builder, Factory, Observer patterns
- **[Package Structure](package-structure.md)** - Source code organization
- **[Request Lifecycle](request-lifecycle.md)** - How evaluation flows through the system
- **[Extensibility](extensibility.md)** - How to extend core functionality

## Core Components

```plaintext
src/
├── Eligify.php                 # Main facade entry point
├── Builder/                    # Criteria and rule builders
├── Engine/                     # Evaluation engine
├── Workflow/                   # Workflow callbacks (onPass, onFail)
├── Audit/                      # Audit logging system
├── Models/                     # Eloquent models
├── Data/                       # Data transfer objects
└── Support/                    # Helper classes
```

## Key Design Principles

1. **Fluent Interface** - Chainable methods for easy criteria building
2. **Dependency Injection** - All components use Laravel's container
3. **Event-Driven** - Emit events at key points in the lifecycle
4. **Separation of Concerns** - Each component has a single responsibility
5. **Testability** - Built with testing in mind

## Related Sections

- [Core Features](../03-core-features/) - Understanding how the architecture is used
- [Data Management](../04-data-management/) - How data flows through the system
- [Advanced Features](../07-advanced-features/) - Extending the architecture
