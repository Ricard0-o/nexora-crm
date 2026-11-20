<?php

declare(strict_types=1);

use App\Validators\LeadValidator;
use PHPUnit\Framework\TestCase;

final class LeadValidatorTest extends TestCase
{
    public function testAcceptsValidLead(): void
    {
        $errors = (new LeadValidator())->validate(['nome_empresa'=>'Acme','ramo_atividade'=>'Tech','email'=>'oi@acme.test','telefone'=>'11999999999']);
        self::assertSame([], $errors);
    }
    public function testRejectsInvalidEmail(): void
    {
        $errors = (new LeadValidator())->validate(['nome_empresa'=>'Acme','ramo_atividade'=>'Tech','email'=>'invalido','telefone'=>'11999999999']);
        self::assertArrayHasKey('email', $errors);
    }
}
