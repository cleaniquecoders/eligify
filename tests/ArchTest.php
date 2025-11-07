<?php

declare(strict_types=1);

// ===== Code Quality Tests =====

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->each->not->toBeUsed();

arch('strict types are declared')
    ->expect('CleaniqueCoders\Eligify')
    ->toUseStrictTypes();

// ===== Architecture Tests =====

arch('Models should extend base Model')
    ->expect('CleaniqueCoders\Eligify\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('Commands should extend base Command')
    ->expect('CleaniqueCoders\Eligify\Commands')
    ->toExtend('Illuminate\Console\Command');

arch('Events should be classes')
    ->expect('CleaniqueCoders\Eligify\Events')
    ->toBeClasses();

arch('Listeners should be classes')
    ->expect('CleaniqueCoders\Eligify\Listeners')
    ->toBeClasses();

arch('Middleware should be classes')
    ->expect('CleaniqueCoders\Eligify\Http\Middleware')
    ->toBeClasses();

arch('Enums should be enums')
    ->expect('CleaniqueCoders\Eligify\Enums')
    ->toBeEnums();

// ===== Naming Conventions =====

arch('Factory classes should end with Factory')
    ->expect('CleaniqueCoders\Eligify\Database\Factories')
    ->toHaveSuffix('Factory');

arch('Observer classes should end with Observer')
    ->expect('CleaniqueCoders\Eligify\Observers')
    ->toHaveSuffix('Observer');
