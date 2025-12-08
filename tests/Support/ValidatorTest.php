<?php

declare(strict_types=1);

namespace Radix\Tests\Support;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Radix\Support\Validator;

class ValidatorTest extends TestCase
{
    public function testStringRulePasses(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'string'];
        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testDotNotationValidationPasses(): void
    {
        $rules = [
            'search.term' => 'required|string|min:1',
            'search.current_page' => 'nullable|integer|min:1',
        ];

        $data = [
            'search' => [
                'term' => 'example',
                'current_page' => 1,
            ],
        ];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testMinRulePassesWithInteger(): void
    {
        $rules = ['current_page' => 'min:1'];
        $data = ['current_page' => 1];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testMinRuleFailsWithInteger(): void
    {
        $rules = ['current_page' => 'min:5'];
        $data = ['current_page' => 2];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    public function testMaxRulePassesWithInteger(): void
    {
        $rules = ['current_page' => 'max:10'];
        $data = ['current_page' => 5];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testMaxRulePassesWhenValueEqualsNumericLimit(): void
    {
        $data = ['current_page' => 10];
        $rules = ['current_page' => 'max:10'];

        $validator = new Validator($data, $rules);

        // Original: 10 <= 10 => true.
        // Mutant (LessThanOrEqualTo): 10 < 10 => false.
        $this->assertTrue(
            $validator->validate(),
            'max:10 ska tillåta värdet 10 för numeriska fält.'
        );
    }

    public function testMaxRuleFailsWithInteger(): void
    {
        $rules = ['current_page' => 'max:5'];
        $data = ['current_page' => 10];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    public function testStringRuleFails(): void
    {
        $data = ['name' => 123];
        $rules = ['name' => 'string'];
        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    public function testRequiredWithPasses(): void
    {
        $data = ['email' => 'test@example.com', 'phone_number' => '123456789'];
        $rules = ['email' => 'required_with:phone_number'];
        $validator = new Validator($data, $rules);

        $this->assertTrue($validator->validate());
    }

    public function testRequiredWithFails(): void
    {
        $data = ['email' => '', 'phone_number' => '123456789'];
        $rules = ['email' => 'required_with:phone_number'];
        $validator = new Validator($data, $rules);

        $this->assertFalse($validator->validate());
    }

    public function testNullable(): void
    {
        $data = ['middle_name' => ''];
        $rules = ['middle_name' => 'nullable|string'];
        $validator = new Validator($data, $rules);

        $this->assertTrue($validator->validate());
    }

    public function testNullableStringPasses(): void
    {
        $data = ['middle_name' => null];
        $rules = ['middle_name' => 'nullable|string'];
        $validator = new Validator($data, $rules);

        $this->assertTrue($validator->validate());
    }

    public function testNullableStringFailsNonString(): void
    {
        $data = ['middle_name' => 123];
        $rules = ['middle_name' => 'nullable|string'];
        $validator = new Validator($data, $rules);

        $this->assertFalse($validator->validate());
    }

    public function testIntegerPasses(): void
    {
        $rules = ['current_page' => 'integer'];
        $data = ['current_page' => 5];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testIntegerPassesForNumericString(): void
    {
        $rules = ['current_page' => 'integer'];
        $data = ['current_page' => '5'];

        $validator = new Validator($data, $rules);

        // Original: is_int('5') == false, is_string('5') && ctype_digit('5') == true => true.
        // Mutanter inverterar and/negation och gör att rena siffersträngar ger false.
        $this->assertTrue(
            $validator->validate(),
            'integer-regeln ska godkänna en sträng som endast innehåller siffror, t.ex. "5".'
        );
    }

    public function testIntegerFailsForNonNumericString(): void
    {
        $rules = ['current_page' => 'integer'];
        $data = ['current_page' => '5a'];

        $validator = new Validator($data, $rules);

        // Original: is_int('5a') == false, is_string('5a') && ctype_digit('5a') == false => false.
        // Mutanter LogicalAnd*/LogicalOrAllSubExprNegation kan returnera true här.
        $this->assertFalse(
            $validator->validate(),
            'integer-regeln ska inte godkänna strängar som innehåller andra tecken än siffror, t.ex. "5a".'
        );
    }

    public function testNullableStringPassesWithString(): void
    {
        $data = ['middle_name' => 'Anders'];
        $rules = ['middle_name' => 'nullable|string'];
        $validator = new Validator($data, $rules);

        $this->assertTrue($validator->validate());
    }



    public function testRequiredRulePasses(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'required'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testRequiredRuleFails(): void
    {
        $data = [];
        $rules = ['name' => 'required'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate(), 'Validering ska misslyckas eftersom `name` krävs, men är tomt.');
    }

    public function testSingleRuleAsString(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'required'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate(), 'Validering med en enkel regel som sträng ska passera.');
    }

    // Email: Passar och misslyckas
    public function testEmailRulePasses(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testEmailRuleFails(): void
    {
        $data = ['email' => 'fel-format'];
        $rules = ['email' => 'email'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    // Min och max längd
    public function testMinRulePasses(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'min:3'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testMinRuleFails(): void
    {
        $data = ['name' => 'Jo'];
        $rules = ['name' => 'min:3'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    public function testMinRulePassesWhenLengthEqualsMin(): void
    {
        $data = ['name' => 'Tom']; // längd 3
        $rules = ['name' => 'min:3'];

        $validator = new Validator($data, $rules);

        // Original: mb_strlen('Tom') = 3 => 3 >= 3 => true.
        // Mutant (GreaterThanOrEqualTo): 3 > 3 => false.
        $this->assertTrue(
            $validator->validate(),
            'min:3 ska tillåta en sträng med exakt 3 tecken.'
        );
    }

    public function testMinRuleFailsForMultibyteCharacterAtBoundary(): void
    {
        $data = ['name' => 'Å']; // 1 tecken, >1 byte
        $rules = ['name' => 'min:2'];

        $validator = new Validator($data, $rules);

        // Original: mb_strlen('Å') = 1 => 1 >= 2 => false.
        // MBString-mutanter använder strlen => 2 >= 2 => true.
        $this->assertFalse(
            $validator->validate(),
            'min:2 ska inte tillåta ett enstaka multibyte-tecken som "Å".'
        );
    }

    public function testMaxRulePasses(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'max:10'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testMaxRuleAllowsNullValue(): void
    {
        $data = ['name' => null];
        $rules = ['name' => 'max:10'];

        $validator = new Validator($data, $rules);

        // Original: is_null(null) || null === '' => true => vi kortsluter till true.
        // LogicalOr-mutanter (&&) samt ReturnRemoval skulle fortsätta och i slutändan ge false.
        $this->assertTrue(
            $validator->validate(),
            'max-regeln ska behandla null som giltig (ingen validering).'
        );
    }

    public function testMaxRulePassesForMultibyteCharacterAtBoundary(): void
    {
        $data = ['name' => 'Å']; // 1 tecken, men >1 byte i UTF-8
        $rules = ['name' => 'max:1'];

        $validator = new Validator($data, $rules);

        // Original: mb_strlen('Å') = 1 => 1 <= 1 => true.
        // MBString-mutanter använder strlen => 2 <= 1 => false.
        // LessThanOrEqualTo-mutanter gör < istället för <= => 1 < 1 => false.
        $this->assertTrue(
            $validator->validate(),
            'max:1 ska tillåta ett enstaka multibyte-tecken som "Å".'
        );
    }

    public function testMaxRuleAllowsEmptyString(): void
    {
        $data = ['name' => ''];
        $rules = ['name' => 'max:10'];

        $validator = new Validator($data, $rules);

        // Original: tom sträng => true (ingen validering).
        // LogicalOr-mutanter med && och TrueValue/ReturnRemoval-mutanter skulle behandla '' som ogiltigt.
        $this->assertTrue(
            $validator->validate(),
            'max-regeln ska behandla tom sträng som giltig (ingen validering).'
        );
    }

    public function testNullableWithConfirmed(): void
    {
        $data = [
            'password' => '', // Tomt värde, ska ignoreras av nullable
            'password_confirmation' => '',
        ];
        $rules = [
            'password' => 'nullable|confirmed:password_confirmation',
        ];

        $validator = new Validator($data, $rules);

        $this->assertTrue($validator->validate(), 'Password ska vara nullable och confirmed ska inte trigga fel');
    }

    public function testPasswordNullableAndConfirmed(): void
    {
        $data = [
            'password' => 'secret123', // Fyllt värde
            'password_confirmation' => 'secret123',
        ];
        $rules = [
            'password' => 'nullable|confirmed',
        ];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate(), 'Validering ska passera för password + confirmed.');
    }

    public function testPasswordNullableConfirmed(): void
    {
        $data = [
            'password' => null,
            'password_confirmation' => null,
        ];

        $rules = [
            'password_confirmation' => 'nullable|required_with:password|confirmed:password',
        ];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate(), 'Validering ska passera eftersom fields är nullable.');
    }

    public function testPasswordConfirmedFails(): void
    {
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'wrong',
        ];

        $rules = [
            'password_confirmation' => 'nullable|required_with:password|confirmed:password',
        ];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate(), 'Valideringen ska misslyckas eftersom fält ej matchar.');
    }

    public function testPasswordConfirmedPasses(): void
    {
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $rules = [
            'password_confirmation' => 'nullable|required_with:password|confirmed:password',
        ];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate(), 'Valideringen ska passera då fälten matchar.');
    }

    public function testMaxRuleFails(): void
    {
        $data = ['name' => 'Jonathan'];
        $rules = ['name' => 'max:5'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    // Numeric validering
    public function testNumericRulePasses(): void
    {
        $data = ['price' => 123];
        $rules = ['price' => 'numeric'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testNumericRuleFails(): void
    {
        $data = ['price' => 'abc'];
        $rules = ['price' => 'numeric'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    public function testNumericRuleAllowsEmptyString(): void
    {
        $data = ['price' => ''];
        $rules = ['price' => 'numeric'];

        $validator = new Validator($data, $rules);

        // Original: tom sträng => true (ingen validering).
        // LogicalOr-mutanter med && skulle gå vidare och ge false.
        $this->assertTrue(
            $validator->validate(),
            'numeric-regeln ska behandla tom sträng som giltig (ingen validering).'
        );
    }

    // Alphanumeric validering
    public function testAlphanumericRulePasses(): void
    {
        $data = ['username' => 'JohnDoe123'];
        $rules = ['username' => 'alphanumeric'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testAlphanumericRuleFails(): void
    {
        $data = ['username' => 'John.Doe!'];
        $rules = ['username' => 'alphanumeric'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    public function testAlphanumericRuleAllowsEmptyString(): void
    {
        $data = ['username' => ''];
        $rules = ['username' => 'alphanumeric'];

        $validator = new Validator($data, $rules);

        // Original: tom sträng => true (ingen validering).
        // LogicalOr-mutanter med && skulle gå vidare och ge false.
        $this->assertTrue(
            $validator->validate(),
            'alphanumeric-regeln ska behandla tom sträng som giltig (ingen validering).'
        );
    }

    // Regex
    public function testRegexRulePasses(): void
    {
        $data = ['username' => 'John123'];
        $rules = ['username' => 'regex:/^[a-zA-Z0-9]+$/'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testRegexRuleFails(): void
    {
        $data = ['username' => 'John_Doe'];
        $rules = ['username' => 'regex:/^[a-zA-Z0-9]+$/'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    public function testRegexRuleAllowsEmptyString(): void
    {
        $data = ['username' => ''];
        $rules = ['username' => 'regex:/^[a-zA-Z0-9]+$/'];

        $validator = new Validator($data, $rules);

        // Original: tom sträng => true (ingen validering).
        // Mutant med && skulle fortsätta och ge false.
        $this->assertTrue(
            $validator->validate(),
            'regex-regeln ska behandla tom sträng som giltig (ingen validering).'
        );
    }

    public function testRegexRuleCastsNonStringScalarToString(): void
    {
        $data = ['code' => 12345]; // int-värde
        $rules = ['code' => 'regex:/^[0-9]+$/'];

        $validator = new Validator($data, $rules);

        // Original: (string) 12345 => "12345" => matchar regex => true.
        // CastString-mutanter utan cast: subject = 12345 (int) => preg_match får fel typ.
        $this->assertTrue(
            $validator->validate(),
            'regex-regeln ska fungera när värdet är ett heltal som matchar mönstret efter sträng-cast.'
        );
    }

    // In och Not in
    public function testInRulePasses(): void
    {
        $data = ['status' => 'active'];
        $rules = ['status' => 'in:active,inactive'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testInRuleAllowsEmptyString(): void
    {
        $data = ['status' => ''];
        $rules = ['status' => 'in:active,inactive'];

        $validator = new Validator($data, $rules);

        // Original: tom sträng => true (ingen validering).
        $this->assertTrue(
            $validator->validate(),
            'in-regeln ska behandla tom sträng som giltig (nullable-liknande beteende).'
        );
    }

    public function testInRuleFailsWhenValueNotInAllowedList(): void
    {
        $data = ['status' => 'pending']; // ej i listan
        $rules = ['status' => 'in:active,inactive'];

        $validator = new Validator($data, $rules);

        // Original: går förbi null/''-check, ser att 'pending' inte finns i listan => false.
        // Mutanter LogicalOr* returnerar true direkt för icke-tomt värde.
        $this->assertFalse(
            $validator->validate(),
            'in-regeln ska misslyckas när värdet inte finns i listan med tillåtna värden.'
        );
    }

    public function testInRuleCastsNonStringScalarToString(): void
    {
        $data = ['status' => 1]; // int-värde
        $rules = ['status' => 'in:1,2,3'];

        $validator = new Validator($data, $rules);

        $this->assertTrue(
            $validator->validate(),
            'in-regeln ska fungera när värdet är ett heltal som matchar en tillåten sträng efter cast.'
        );
    }

    public function testNotInRulePasses(): void
    {
        $data = ['role' => 'viewer'];
        $rules = ['role' => 'not_in:admin,superuser'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testNotInRuleFailsForDisallowedString(): void
    {
        $data = ['role' => 'admin'];
        $rules = ['role' => 'not_in:admin,superuser'];

        $validator = new Validator($data, $rules);

        // Original: 'admin' finns i disallowed → ska returnera false.
        // LogicalNot-mutanter godkänner alla scalars direkt (return true).
        $this->assertFalse(
            $validator->validate(),
            'not_in ska misslyckas när värdet finns i den otillåtna listan.'
        );
    }

    public function testNotInRuleFailsForDisallowedInt(): void
    {
        $data = ['code' => 1]; // int-värde
        $rules = ['code' => 'not_in:1,2,3'];

        $validator = new Validator($data, $rules);

        // Original: (string)1 => '1' finns i listan => in_array === true => !true => false.
        // CastString-mutanter utan cast: valueString = 1 (int) => strict in_array([... '1','2','3' ...]) => false => !false => true.
        $this->assertFalse(
            $validator->validate(),
            'not_in ska misslyckas även när värdet är ett heltal som matchar en otillåten sträng efter cast.'
        );
    }

    public function testUniqueRuleThrowsForNonStringModelClass(): void
    {
        $data = ['email' => 'test@example.com'];
        // Första delen ('123') är inte en giltig klass-sträng
        $rules = ['email' => 'unique:123,email'];

        $validator = new Validator($data, $rules);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Valideringsregeln 'unique' kräver en giltig modellklass.");

        $validator->validate();
    }

    public function testUniqueRuleThrowsForEmptyModelClass(): void
    {
        $data = ['email' => 'test@example.com'];
        // Tom modellklass, men giltig kolumn
        $rules = ['email' => 'unique:,email'];

        $validator = new Validator($data, $rules);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Valideringsregeln 'unique' kräver en giltig modellklass.");

        $validator->validate();
    }

    // IP validering
    public function testIpRulePasses(): void
    {
        $data = ['server' => '192.168.1.1'];
        $rules = ['server' => 'ip'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testIpRuleFails(): void
    {
        $data = ['server' => 'not-an-ip'];
        $rules = ['server' => 'ip'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    public function testIpRuleAllowsEmptyString(): void
    {
        $data = ['server' => ''];
        $rules = ['server' => 'ip'];

        $validator = new Validator($data, $rules);
        $this->assertTrue(
            $validator->validate(),
            'IP-regeln ska betrakta tom sträng som giltig (nullable-liknande beteende).'
        );
    }

    // Boolean
    public function testBooleanRulePasses(): void
    {
        $data = ['isActive' => true];
        $rules = ['isActive' => 'boolean'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testBooleanRuleFails(): void
    {
        $data = ['isActive' => 'yes'];
        $rules = ['isActive' => 'boolean'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    public function testBooleanRulePassesWithFalse(): void
    {
        $data = ['isActive' => false];
        $rules = ['isActive' => 'boolean'];

        $validator = new Validator($data, $rules);

        // Original: is_bool(false) => true.
        // ReturnRemoval-mutanter fortsätter och hamnar till slut på in_array('' eller false, ...) => false.
        $this->assertTrue(
            $validator->validate(),
            'boolean-regeln ska godkänna det rena boolska värdet false.'
        );
    }

    public function testBooleanRulePassesWithStringOne(): void
    {
        $data = ['isActive' => '1'];
        $rules = ['isActive' => 'boolean'];

        $validator = new Validator($data, $rules);

        // Original: !is_scalar är false, castar till '1' och in_array('1', ...) => true.
        // LogicalNot-mutanter vänder if (!is_scalar) till if (is_scalar) och returnerar false direkt.
        $this->assertTrue(
            $validator->validate(),
            "boolean-regeln ska godkänna strängen '1' som sant."
        );
    }

    public function testBooleanRulePassesWithIntOne(): void
    {
        $data = ['isActive' => 1];
        $rules = ['isActive' => 'boolean'];

        $validator = new Validator($data, $rules);

        // Original: (string) 1 => '1' => in_array('1', ...) => true.
        // CastString-mutanter slopar casten, valueString blir int 1 och strict in_array misslyckas.
        $this->assertTrue(
            $validator->validate(),
            'boolean-regeln ska godkänna heltalet 1 som sant.'
        );
    }

    // Date och DateFormat
    public function testDateRulePasses(): void
    {
        $data = ['birthday' => '2023-07-31'];
        $rules = ['birthday' => 'date'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testDateRuleFailsWithInvalidValue(): void
    {
        $data = ['birthday' => 'not-a-date'];
        $rules = ['birthday' => 'date'];

        $validator = new Validator($data, $rules);

        // Original: is_scalar + strtotime('not-a-date') === false => false.
        // Mutant 104: skulle kortsluta till true för vissa icke-tomma värden.
        $this->assertFalse(
            $validator->validate(),
            'date-regeln ska misslyckas för ett värde som inte kan parsas som datum.'
        );
    }

    public function testDateRuleAllowsEmptyString(): void
    {
        $data = ['birthday' => ''];
        $rules = ['birthday' => 'date'];

        $validator = new Validator($data, $rules);

        // Original: tom sträng => true.
        // Mutant 104 (negation av första subexpr) skulle gå vidare och kunna ge false.
        $this->assertTrue(
            $validator->validate(),
            'date-regeln ska behandla tom sträng som giltig (ingen validering).'
        );
    }

    public function testDateRuleCastsNonStringScalarToString(): void
    {
        $data = ['birthday' => 20230731]; // int-värde
        $rules = ['birthday' => 'date'];

        $validator = new Validator($data, $rules);

        // Original: (string) 20230731 => "20230731" => strtotime("20230731") !== false => true.
        // Mutant 105 utan cast ger fel typ till strtotime.
        $this->assertTrue(
            $validator->validate(),
            'date-regeln ska fungera när värdet är ett heltal som kan tolkas som datum efter sträng-cast.'
        );
    }

    public function testDateFormatRulePasses(): void
    {
        $data = ['start_date' => '31-07-2023'];
        $rules = ['start_date' => 'date_format:d-m-Y'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testDateFormatRuleFailsWithInvalidValue(): void
    {
        $data = ['start_date' => 'not-a-date'];
        $rules = ['start_date' => 'date_format:d-m-Y'];

        $validator = new Validator($data, $rules);
        $this->assertFalse(
            $validator->validate(),
            'date_format:d-m-Y ska misslyckas för ett värde som inte matchar formatet alls.'
        );
    }

    public function testDateFormatAllowsEmptyString(): void
    {
        $data = ['start_date' => ''];
        $rules = ['start_date' => 'date_format:d-m-Y'];

        $validator = new Validator($data, $rules);

        // Original: tom sträng => true.
        // Mutant med && skulle försöka validera vidare och i praktiken falla ut som false.
        $this->assertTrue(
            $validator->validate(),
            'date_format-regeln ska behandla tom sträng som giltig (ingen validering).'
        );
    }

    public function testDateFormatCastsNonStringScalarToString(): void
    {
        $data = ['start_date' => 20230731]; // int-värde
        $rules = ['start_date' => 'date_format:Ymd'];

        $validator = new Validator($data, $rules);

        // Original: (string) 20230731 => "20230731" => giltigt datum i formatet Ymd.
        // Mutant utan cast: valueString är int => DateTime::createFromFormat får fel typ och kraschar.
        $this->assertTrue(
            $validator->validate(),
            'date_format ska fungera även när värdet är ett heltal som matchar datumformatet efter sträng-cast.'
        );
    }

    // StartsWith och EndsWith
    public function testStartsWithRulePasses(): void
    {
        $data = ['username' => 'admin_user'];
        $rules = ['username' => 'starts_with:admin'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }


    public function testStartsWithCastsNonStringScalarToString(): void
    {
        $data = ['number' => 12345]; // int-värde
        $rules = ['number' => 'starts_with:12'];

        $validator = new Validator($data, $rules);

        $this->assertTrue(
            $validator->validate(),
            'starts_with ska fungera även när värdet är ett heltal som matchar prefixet efter sträng-cast.'
        );
    }

    public function testStartsWithFailsOnNonScalarValue(): void
    {
        $data = ['username' => ['not', 'scalar']];
        $rules = ['username' => 'starts_with:admin'];

        $validator = new Validator($data, $rules);

        // Original: !is_scalar($value) => false returneras.
        // Mutanter: första if-satsen blir sann även för array => returnerar true.
        $this->assertFalse(
            $validator->validate(),
            'starts_with ska returnera false för icke-skalära värden (t.ex. array).'
        );
    }

    public function testStartsWithAllowsEmptyString(): void
    {
        $data = ['username' => ''];
        $rules = ['username' => 'starts_with:admin'];

        $validator = new Validator($data, $rules);

        // Original: tom sträng => true.
        // Mutant med && skulle validera hela vägen och returnera false.
        $this->assertTrue(
            $validator->validate(),
            'starts_with-regeln ska behandla tom sträng som giltig (ingen validering).'
        );
    }

    public function testEndsWithRulePasses(): void
    {
        $data = ['file' => 'report.pdf'];
        $rules = ['file' => 'ends_with:.pdf'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }


    public function testEndsWithRuleFails(): void
    {
        $data = ['file' => 'report.txt'];
        $rules = ['file' => 'ends_with:.pdf'];

        $validator = new Validator($data, $rules);
        $this->assertFalse(
            $validator->validate(),
            'ends_with:.pdf ska inte godkänna en fil som slutar på .txt.'
        );
    }

    public function testEndsWithCastsNonStringScalarToString(): void
    {
        $data = ['number' => 12345]; // int-värde
        $rules = ['number' => 'ends_with:45'];

        $validator = new Validator($data, $rules);

        // Original: (string) 12345 => "12345" → slutar med "45" → true.
        // Mutant utan cast: valueString är int => str_ends_with får fel typ och kraschar.
        $this->assertTrue(
            $validator->validate(),
            'ends_with ska fungera även när värdet är ett heltal som matchar suffixet efter sträng-cast.'
        );
    }

    public function testEndsWithAllowsEmptyString(): void
    {
        $data = ['file' => ''];
        $rules = ['file' => 'ends_with:.pdf'];

        $validator = new Validator($data, $rules);

        // Original: tom sträng ska vara "ok" (som övriga nullable-liknande regler).
        // Mutant med && skulle försöka validera vidare och i praktiken falla ut som false.
        $this->assertTrue(
            $validator->validate(),
            'ends_with-regeln ska behandla tom sträng som giltig (ingen validering).'
        );
    }

    public function testGetValueForDotNotationReturnsNullWhenIntermediateIsNotArray(): void
    {
        $data = [
            'user' => 'not-array', // fel struktur
        ];
        $rules = [];

        $validator = new TestableValidator($data, $rules);

        // Original: ska lugnt returnera null utan fel
        $this->assertNull($validator->getDotValue('user.name'));
    }

    /**
     * Valideraren ska omedelbart avbryta och returnera false vid filuppladdningsfel,
     * samt sätta korrekt felmeddelande endast på 'file'.
     *
     * Dödar mutanter:
     * - NotIdentical (error === UPLOAD_ERR_OK)
     * - ArrayItemRemoval (errors['file'] = [])
     * - ReturnRemoval (saknat early return)
     */
    public function testValidateStopsAndSetsErrorOnFileUploadError(): void
    {
        $data = [
            'error' => UPLOAD_ERR_INI_SIZE, // vilket annat fel än UPLOAD_ERR_OK
            // notera: inget 'name' trots regel nedan
        ];
        $rules = [
            'name' => 'required',
        ];

        $validator = new Validator($data, $rules);

        $this->assertFalse(
            $validator->validate(),
            'Validering ska misslyckas direkt vid filuppladdningsfel.'
        );

        $errors = $validator->errors();
        $this->assertArrayHasKey('file', $errors, 'Filfel ska registreras på nyckeln "file".');
        $this->assertSame(
            ['Filen laddades inte upp korrekt.'],
            $errors['file'],
            'Felmeddelandet för filfel ska vara oförändrat.'
        );

        // Om return-satsen tas bort fortsätter valideringen och
        // genererar även fel för "name". Det ska INTE ske.
        $this->assertCount(
            1,
            $errors,
            'Endast filfelet ska finnas registrerat när filuppladdningen misslyckas.'
        );
    }

    /**
     * addError ska vara anropbar publikt och som default INTE stoppa in värdet i meddelandet.
     *
     * Dödar mutanter:
     * - FalseValue (default includeValue = true)
     * - PublicVisibility (protected i stället för public)
     */
    public function testAddErrorDoesNotIncludeValueByDefaultAndIsPublic(): void
    {
        $data = ['field' => 'ABC'];
        $rules = [];

        $validator = new Validator($data, $rules);

        // Om signaturen ändras till protected går detta anrop inte längre.
        $validator->addError('field', 'Värdet är {placeholder}');

        $errors = $validator->errors();
        $this->assertArrayHasKey('field', $errors);

        // Default-beteende: placeholdern ska finnas kvar oförändrad.
        $this->assertSame(
            ['Värdet är {placeholder}'],
            $errors['field'],
            'addError ska inte ersätta {placeholder} när includeValue inte är satt.'
        );
    }

    /**
     * När includeValue=true och värdet är ett skalärt värde, ska {placeholder}
     * ersättas med strängrepresentationen av värdet.
     *
     * Säkerställer korrekt grundbeteende och hjälper mot mutanter i det logiska uttrycket.
     */
    public function testAddErrorIncludesScalarValueWhenFlagIsTrue(): void
    {
        $data = ['age' => 42];
        $rules = [];

        $validator = new Validator($data, $rules);
        $validator->addError('age', 'Ålder är {placeholder}', true);

        $errors = $validator->errors();
        $this->assertSame(
            ['Ålder är 42'],
            $errors['age'],
            'När includeValue=true och värdet är satt ska {placeholder} ersättas med värdet.'
        );
    }

    /**
     * När includeValue=true men värdet är null ska placeholdern INTE ersättas.
     *
     * Dödar mutanter:
     * - NotIdentical (villkor $value === null)
     * - LogicalAnd (bytt till ||, dvs includeValue || $value !== null)
     */
    public function testAddErrorDoesNotIncludeValueWhenNullEvenIfFlagIsTrue(): void
    {
        $data = ['note' => null];
        $rules = [];

        $validator = new Validator($data, $rules);
        $validator->addError('note', 'Notering: {placeholder}', true);

        $errors = $validator->errors();
        $this->assertSame(
            ['Notering: {placeholder}'],
            $errors['note'],
            'När värdet är null ska {placeholder} lämnas orörd även om includeValue=true.'
        );
    }

    /**
     * När includeValue=false, oavsett om värdet är null eller inte, ska placeholdern
     * inte ersättas.
     *
     * Dödar mutant:
     * - LogicalAndAllSubExprNegation (villkor !$includeValue && !($value !== null))
     */
    public function testAddErrorDoesNotIncludeValueWhenFlagIsFalse(): void
    {
        $data = ['status' => 'active'];
        $rules = [];

        $validator = new Validator($data, $rules);
        $validator->addError('status', 'Status: {placeholder}', false);

        $errors = $validator->errors();
        $this->assertSame(
            ['Status: {placeholder}'],
            $errors['status'],
            'När includeValue=false ska {placeholder} inte ersättas, även om värdet är satt.'
        );
    }
}
