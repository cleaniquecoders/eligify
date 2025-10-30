# Extractor Architecture

This document explains how the `Extractor` fits into the overall Eligify architecture and data flow.

## System Overview

```mermaid
flowchart TB
    subgraph Input["📥 Input Layer"]
        Model[Your Eloquent Model<br/>User, LoanApplication, etc.]
    end

    subgraph Extraction["🔄 Data Extraction Layer"]
        MDE[Extractor]
        Config[Config File<br/>eligify.php]
        Mapping[Mapping Classes<br/>UserMapping, etc.]

        Config -.->|configures| MDE
        Mapping -.->|applies to| MDE
    end

    subgraph Processing["⚙️ Eligibility Processing"]
        FlatData[Flat Array<br/>All model data normalized]
        Rules[Rule Engine<br/>Evaluates criteria]
        Evaluator[Evaluator<br/>Runs checks]
    end

    subgraph Output["📤 Output Layer"]
        Result[Evaluation Result<br/>passed/failed + details]
        Actions[Workflows<br/>Callbacks triggered]
        Audit[Audit Logs<br/>Decision trail]
    end

    Model -->|extract| MDE
    MDE -->|produces| FlatData
    FlatData -->|feeds into| Rules
    Rules -->|checked by| Evaluator
    Evaluator -->|returns| Result
    Result -->|triggers| Actions
    Result -->|logged in| Audit

    style Model fill:#E3F2FD
    style MDE fill:#FFF9C4
    style FlatData fill:#C8E6C9
    style Result fill:#BBDEFB
```

## Data Extraction Pipeline

```mermaid
flowchart LR
    subgraph "1. Model"
        M[User Model<br/>Complex nested data]
    end

    subgraph "2. Extractor"
        E[Extractor<br/>Transforms & flattens]
    end

    subgraph "3. Flat Data"
        F[income: 5000<br/>credit_score: 720<br/>orders_count: 12<br/>account_age_days: 365]
    end

    subgraph "4. Rules"
        R[Income >= 3000<br/>Credit >= 650<br/>Orders >= 5<br/>Age >= 180]
    end

    subgraph "5. Result"
        Result[✅ All Passed<br/>Eligible: true]
    end

    M --> E
    E --> F
    F --> R
    R --> Result

    style M fill:#E3F2FD
    style E fill:#FFF9C4
    style F fill:#C8E6C9
    style R fill:#FFE082
    style Result fill:#A5D6A7
```

## Why Extractor Exists

### Problem: Complex Model Structures

```mermaid
flowchart TB
    subgraph "❌ Without Extractor"
        U1[User Model] --> R1[Rules Engine]
        R1 -.->|"How to access<br/>user->profile->income?"| Problem1[Complex nested access]
        R1 -.->|"How to get<br/>orders count?"| Problem2[Manual aggregations]
        R1 -.->|"How to calculate<br/>account age?"| Problem3[Computed fields logic]
    end

    subgraph "✅ With Extractor"
        U2[User Model] --> MDE[Extractor]
        MDE --> Flat[income: 5000<br/>orders_count: 12<br/>account_age_days: 365]
        Flat --> R2[Rules Engine]
        R2 --> Success[Simple field access]
    end

    style Problem1 fill:#FFCDD2
    style Problem2 fill:#FFCDD2
    style Problem3 fill:#FFCDD2
    style Success fill:#C8E6C9
    style MDE fill:#FFF9C4
```

## Configuration Hierarchy

```mermaid
flowchart TB
    subgraph "Configuration Sources (Priority Order)"
        P1[1. Method Calls<br/>setFieldMappings, setComputedFields]
        P2[2. Model Mapping Class<br/>UserMapping::configure]
        P3[3. Config File<br/>config/eligify.php]
        P4[4. Default Config<br/>Built-in defaults]
    end

    P1 -->|overrides| P2
    P2 -->|overrides| P3
    P3 -->|overrides| P4

    P4 --> Final[Final Configuration]

    style P1 fill:#81C784
    style P2 fill:#AED581
    style P3 fill:#DCE775
    style P4 fill:#FFF59D
    style Final fill:#FFF9C4
```

## Usage Pattern Comparison

```mermaid
flowchart TB
    subgraph "Pattern 1: Quick"
        Q1[new Extractor] --> Q2[extract model]
        Q2 --> Q3[Uses defaults only]
    end

    subgraph "Pattern 2: Custom"
        C1[new Extractor] --> C2[setFieldMappings<br/>setComputedFields]
        C2 --> C3[extract model]
        C3 --> C4[Uses custom config]
    end

    subgraph "Pattern 3: Production"
        P1[forModel User::class] --> P2[Looks up config]
        P2 --> P3[Applies UserMapping]
        P3 --> P4[extract model]
        P4 --> P5[Uses production config]
    end

    style Q3 fill:#FFD700
    style C4 fill:#87CEEB
    style P5 fill:#90EE90
```

## Real-World Flow Example

Here's how a complete loan approval flow works with Extractor:

```mermaid
sequenceDiagram
    participant C as Controller
    participant E as Eligify Facade
    participant M as Extractor
    participant R as Rule Engine
    participant A as Audit Logger
    participant W as Workflow

    C->>E: evaluate('loan_approval', $application)
    E->>M: forModel(LoanApplication::class)
    Note over M: Loads LoanMapping from config
    M->>M: Apply field mappings
    M->>M: Apply computed fields
    M-->>E: Configured extractor

    E->>M: extract($application)
    M->>M: Extract attributes
    M->>M: Extract relationships
    M->>M: Calculate computed fields
    M-->>E: Flat data array

    E->>R: evaluate(data, rules)
    R->>R: Check each rule
    R-->>E: Result (passed/failed)

    E->>A: Log decision
    A-->>E: Audit record created

    E->>W: Trigger callbacks
    W->>W: Execute onPass/onFail
    W-->>E: Actions completed

    E-->>C: Final result with details
```

## Data Transformation Example

```mermaid
flowchart LR
    subgraph "Input: Complex Model"
        I["User {<br/>  id: 1<br/>  email: 'user@example.com'<br/>  created_at: '2024-01-15'<br/>  profile: {<br/>    annual_income: 60000<br/>    employment_status: 'employed'<br/>  }<br/>  orders: [Order, Order, Order]<br/>}"]
    end

    subgraph "Extractor"
        E[Extract<br/>Map<br/>Compute<br/>Flatten]
    end

    subgraph "Output: Flat Array"
        O["[<br/>  'id' => 1,<br/>  'email' => 'user@example.com',<br/>  'income' => 60000,<br/>  'is_employed' => true,<br/>  'orders_count' => 3,<br/>  'account_age_days' => 287,<br/>  'email_verified' => true<br/>]"]
    end

    I -->|Complex nested data| E
    E -->|Simple flat data| O

    style I fill:#FFCDD2
    style E fill:#FFF9C4
    style O fill:#C8E6C9
```

## Key Benefits

```mermaid
mindmap
  root((Extractor))
    Simplification
      Flattens nested data
      Consistent field names
      No manual queries
    Reusability
      Centralized logic
      Config-driven
      Type-safe mappings
    Maintainability
      Single source of truth
      Easy to test
      Version controlled
    Performance
      Optimized queries
      Cached computations
      Minimal overhead
```

## Integration Points

```mermaid
flowchart TB
    subgraph "External Systems"
        Laravel[Laravel Models]
        DB[(Database)]
    end

    subgraph "Eligify Core"
        MDE[Extractor]
        Engine[Rule Engine]
        Builder[Criteria Builder]
        Eval[Evaluator]
    end

    subgraph "Extensions"
        Mappings[Model Mappings]
        Policies[Laravel Policies]
        Events[Events/Listeners]
    end

    Laravel -->|provides| MDE
    DB -->|data| Laravel
    MDE -->|feeds| Engine
    Engine -->|uses| Builder
    Engine -->|runs| Eval

    Mappings -.->|configures| MDE
    Eval -.->|can trigger| Policies
    Eval -.->|fires| Events

    style MDE fill:#FFF9C4
    style Engine fill:#FFE082
```

## Summary

The `Extractor` is a critical bridge component that:

1. **Transforms** complex model structures into flat, rule-friendly arrays
2. **Standardizes** data extraction across different model types
3. **Centralizes** field mapping and computation logic
4. **Enables** consistent, testable, and maintainable eligibility rules

It sits between your Laravel models and the Eligify rule engine, making eligibility evaluation simple and powerful.

## Related Documentation

- [Quick Reference](quick-reference-model-extraction.md) - One-page usage guide
- [Complete Guide](model-data-extraction.md) - Detailed documentation with examples
- [Model Mappings](model-mappings.md) - How to create mapping classes
- [Configuration](configuration.md) - Config options
- [Usage Guide](usage-guide.md) - End-to-end examples
