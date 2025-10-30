# Workflow Callbacks

Workflow callbacks allow you to execute actions based on evaluation results automatically.

## Overview

Workflows enable you to:
- Trigger actions when evaluation passes
- Handle failure cases
- Automate business processes
- Send notifications
- Update records
- Execute custom logic

## OnPass Callbacks

Execute when evaluation passes:

```php
Eligify::criteria('loan_approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->onPass(function ($subject) {
        $subject->approve();
        Notification::send($subject->user, new LoanApproved());
    })
    ->evaluate($application);
```

### Multiple OnPass Callbacks

```php
->onPass(function ($subject) {
    $subject->approve();
})
->onPass(function ($subject) {
    event(new ApplicationApproved($subject));
})
->onPass(function ($subject) {
    Cache::tags('applications')->flush();
})
```

## OnFail Callbacks

Execute when evaluation fails:

```php
Eligify::criteria('loan_approval')
    ->addRule('income', '>=', 3000)
    ->addRule('credit_score', '>=', 650)
    ->onFail(function ($subject) {
        $subject->reject();
        Log::warning('Loan application rejected', [
            'application_id' => $subject->id,
        ]);
    })
    ->evaluate($application);
```

## Accessing Result in Callbacks

Callbacks receive both subject and result:

```php
->onPass(function ($subject, $result) {
    $subject->approval_score = $result->score();
    $subject->approved_at = now();
    $subject->save();
})
->onFail(function ($subject, $result) {
    $subject->rejection_reason = $result->failedRules();
    $subject->save();
})
```

## Common Use Cases

### 1. Approval Workflows

```php
Eligify::criteria('application_approval')
    ->addRule('documents_complete', '==', true)
    ->addRule('background_check', '==', 'passed')
    ->onPass(function ($application) {
        $application->update(['status' => 'approved']);
        $application->user->notify(new Approved($application));
        event(new ApplicationApproved($application));
    })
    ->onFail(function ($application, $result) {
        $application->update([
            'status' => 'rejected',
            'rejection_reasons' => $result->failedRules(),
        ]);
        $application->user->notify(new Rejected($application));
    });
```

### 2. Membership Upgrades

```php
Eligify::criteria('premium_upgrade')
    ->addRule('points', '>=', 1000)
    ->addRule('active_months', '>=', 6)
    ->onPass(function ($user) {
        $user->upgradeToPremium();
        $user->notify(new PremiumUpgraded());

        // Grant premium features
        $user->grantPermission('premium_features');

        // Log upgrade
        activity()
            ->performedOn($user)
            ->withProperties(['upgraded_to' => 'premium'])
            ->log('membership_upgraded');
    })
    ->onFail(function ($user, $result) {
        // Send reminder about premium benefits
        $user->notify(new PremiumUpgradeReminder([
            'points_needed' => 1000 - $user->points,
            'months_needed' => 6 - $user->active_months,
        ]));
    });
```

### 3. Content Publishing

```php
Eligify::criteria('publish_check')
    ->addRule('word_count', '>=', 500)
    ->addRule('images_count', '>=', 1)
    ->addRule('seo_score', '>=', 70)
    ->onPass(function ($post) {
        $post->publish();
        event(new PostPublished($post));
    })
    ->onFail(function ($post, $result) {
        $post->update(['status' => 'draft']);
        $post->author->notify(new PublishRequirementsFailed([
            'post' => $post,
            'failed_checks' => $result->failedRules(),
        ]));
    });
```

### 4. Access Control

```php
Eligify::criteria('admin_access')
    ->addRule('role', 'in', ['admin', 'super_admin'])
    ->addRule('2fa_enabled', '==', true)
    ->addRule('ip_whitelisted', '==', true)
    ->onPass(function ($user) {
        session(['admin_access_granted' => true]);
        Log::info('Admin access granted', ['user_id' => $user->id]);
    })
    ->onFail(function ($user) {
        session(['admin_access_denied' => true]);
        Log::warning('Admin access denied', ['user_id' => $user->id]);
        $user->notify(new AccessDenied());
    });
```

## Async Workflows

Dispatch jobs for long-running operations:

```php
->onPass(function ($subject) {
    ProcessApprovalJob::dispatch($subject);
    SendNotificationJob::dispatch($subject, 'approved');
    UpdateStatisticsJob::dispatch();
})
```

## Conditional Workflows

Execute callbacks based on additional conditions:

```php
->onPass(function ($subject, $result) {
    if ($result->score() >= 90) {
        $subject->markAsPriority();
    }

    if ($subject->isFirstTime()) {
        $subject->grantWelcomeBonus();
    }
})
```

## Error Handling in Workflows

```php
->onPass(function ($subject) {
    try {
        $subject->approve();
        event(new Approved($subject));
    } catch (\Exception $e) {
        Log::error('Approval workflow failed', [
            'subject_id' => $subject->id,
            'error' => $e->getMessage(),
        ]);

        // Rollback or handle error
        $subject->revert();
    }
})
```

## Database Transactions

Wrap workflows in transactions:

```php
->onPass(function ($subject) {
    DB::transaction(function () use ($subject) {
        $subject->approve();
        $subject->user->addPoints(100);
        $subject->user->incrementLevel();
    });
})
```

## Workflow Events

Eligify fires events during workflow execution:

```php
// Listen to workflow events
Event::listen(WorkflowExecuted::class, function ($event) {
    Log::info('Workflow executed', [
        'type' => $event->type, // 'onPass' or 'onFail'
        'criteria' => $event->criteria->name,
        'subject_id' => $event->subject->id,
    ]);
});
```

## Testing Workflows

```php
use CleaniqueCoders\Eligify\Facades\Eligify;

test('onPass callback executes when criteria pass', function () {
    $user = User::factory()->create(['points' => 1500]);

    $callbackExecuted = false;

    Eligify::criteria('test')
        ->addRule('points', '>=', 1000)
        ->onPass(function () use (&$callbackExecuted) {
            $callbackExecuted = true;
        })
        ->evaluate($user);

    expect($callbackExecuted)->toBeTrue();
});

test('onFail callback executes when criteria fail', function () {
    $user = User::factory()->create(['points' => 500]);

    $callbackExecuted = false;

    Eligify::criteria('test')
        ->addRule('points', '>=', 1000)
        ->onFail(function () use (&$callbackExecuted) {
            $callbackExecuted = true;
        })
        ->evaluate($user);

    expect($callbackExecuted)->toBeTrue();
});
```

## Best Practices

### 1. Keep Callbacks Focused

```php
// Good: Single responsibility
->onPass(function ($subject) {
    $subject->approve();
})

// Bad: Too many responsibilities
->onPass(function ($subject) {
    $subject->approve();
    $subject->sendEmail();
    $subject->updateCache();
    $subject->logActivity();
    $subject->notifyAdmin();
    // ... too much
})
```

### 2. Use Jobs for Heavy Work

```php
// Good: Dispatch job
->onPass(function ($subject) {
    ProcessApprovalJob::dispatch($subject);
})

// Bad: Heavy work in callback
->onPass(function ($subject) {
    // Long-running process
    $subject->generateReports();
    $subject->sendBulkEmails();
    $subject->updateAnalytics();
})
```

### 3. Handle Errors Gracefully

```php
->onPass(function ($subject) {
    try {
        $subject->approve();
    } catch (\Exception $e) {
        report($e);
        // Fallback logic
    }
})
```

### 4. Use Events for Decoupling

```php
// Good: Decouple with events
->onPass(function ($subject) {
    event(new EligibilityPassed($subject));
})

// Bad: Tight coupling
->onPass(function ($subject) {
    app(NotificationService::class)->send($subject);
    app(CacheService::class)->clear();
    app(AnalyticsService::class)->track();
})
```

## Workflow Patterns

### Approval Chain

```php
Eligify::criteria('multi_stage_approval')
    ->addRule('stage_1_approved', '==', true)
    ->addRule('stage_2_approved', '==', true)
    ->onPass(function ($application) {
        $application->moveToNextStage();
    })
    ->onFail(function ($application) {
        $application->sendBackForRevision();
    });
```

### Notification Cascade

```php
->onPass(function ($subject, $result) {
    // Notify subject
    $subject->user->notify(new Approved());

    // Notify admin
    User::admin()->each->notify(new NewApproval($subject));

    // Notify stakeholders
    event(new ApprovalGranted($subject));
})
```

### Auto-Escalation

```php
->onFail(function ($subject, $result) {
    if ($result->score() >= 60) {
        // Close to passing, escalate for manual review
        $subject->escalateToManager();
    } else {
        // Far from passing, auto-reject
        $subject->reject();
    }
})
```

## Related Documentation

- [Criteria Builder](criteria-builder.md) - Building criteria
- [Evaluation Engine](evaluation-engine.md) - Evaluation process
- [Events](../14-reference/events.md) - Event system
