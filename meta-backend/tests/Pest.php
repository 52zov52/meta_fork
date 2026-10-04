<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature: RefreshDatabase + отдельная БД (phpunit.xml; БД создаёт init MySQL, см. docker/mysql-init.sh).
| Unit с БД: локально uses(TestCase::class, RefreshDatabase::class), см. AcademyServiceTest.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');
