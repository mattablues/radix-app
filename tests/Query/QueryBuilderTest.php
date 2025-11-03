<?php

declare(strict_types=1);

namespace Radix\Tests\Query;

use PHPUnit\Framework\TestCase;
use Radix\Database\Connection;
use Radix\Database\QueryBuilder\QueryBuilder;

class QueryBuilderTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = new \PDO('mysql:host=localhost;dbname=test', 'root', 'root');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->connection = new Connection($pdo);
    }

    public function testNestedWhere()
    {
        $query = (new QueryBuilder())
            ->from('users')
            ->where('status', '=', 'active')
            ->where(function ($q) {
                $q->where('age', '>=', 18)
                  ->where('country', '=', 'Sweden');
            })
            ->where('deleted_at', 'IS', null);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `status` = ? AND (`age` >= ? AND `country` = ?) AND `deleted_at` IS NULL',
            $query->toSql()
        );

        $this->assertEquals(
            ['active', 18, 'Sweden'],
            $query->getBindings()
        );
    }

    public function testSimpleSelectQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users');

        $this->assertEquals(
            'SELECT * FROM `users`',
            $query->toSql(),
            'QueryBuilder should generate a simple SELECT query.'
        );
    }

    public function testSelectWithAlias(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users AS u');

        $this->assertEquals(
            'SELECT * FROM `users` AS `u`',
            $query->toSql(),
            'QueryBuilder should handle table aliases correctly in a SELECT query.'
        );
    }

    public function testWithSoftDeletesRemovesDeletedAtFilter(): void
    {
        // Setup QueryBuilder och aktivera withSoftDeletes
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->withSoftDeletes() // Aktivera soft deletes
            ->where('email', '=', 'test@example.com');

        // Kontrollera att 'deleted_at IS NULL' inte inkluderas i SQL-frågan
        $this->assertEquals(
            'SELECT * FROM `users` WHERE `email` = ?',
            $query->toSql(),
            'QueryBuilder should not include deleted_at filter when withSoftDeletes is active.'
        );

        // Kontrollera att bindningarna endast innehåller parametern för email
        $this->assertEquals(
            ['test@example.com'],
            $query->getBindings(),
            'QueryBuilder should bind the WHERE clause values correctly.'
        );
    }

    public function testSelectWithWhereClause(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('id', '=', 1);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `id` = ?',
            $query->toSql(),
            'QueryBuilder should generate a SELECT query with WHERE clause.'
        );

        $this->assertEquals(
            [1],
            $query->getBindings(),
            'QueryBuilder should bind the WHERE clause values correctly.'
        );
    }

    public function testJoinQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->join('profiles', 'users.id', '=', 'profiles.user_id');

        $this->assertEquals(
            'SELECT * FROM `users` INNER JOIN `profiles` ON `users`.`id` = `profiles`.`user_id`',
            $query->toSql(),
            'QueryBuilder should generate a SELECT query with JOIN clause.'
        );
    }

    public function testComplexQueryWithPagination(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['users.id', 'users.name'])
            ->from('users')
            ->where('status', '=', 'active')
            ->orderBy('users.name')
            ->limit(10)
            ->offset(20);

        $this->assertEquals(
            'SELECT `users`.`id`, `users`.`name` FROM `users` WHERE `status` = ? ORDER BY `users`.`name` ASC LIMIT 10 OFFSET 20',
            $query->toSql(),
            'QueryBuilder should generate a SELECT query with WHERE, ORDER BY, LIMIT, and OFFSET clauses.'
        );

        $this->assertEquals(
            ['active'],
            $query->getBindings(),
            'QueryBuilder should bind the WHERE clause values correctly for a complex query.'
        );
    }

    public function testSearchQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['users.id', 'users.name'])
            ->from('users')
            ->whereNull('deleted_at')
            ->where('users.name', 'LIKE', '%John%')
            ->orWhere('users.email', 'LIKE', '%john@example.com%')
            ->limit(10)
            ->offset(0);

        $this->assertEquals(
            'SELECT `users`.`id`, `users`.`name` FROM `users` WHERE `deleted_at` IS NULL AND `users`.`name` LIKE ? OR `users`.`email` LIKE ? LIMIT 10 OFFSET 0',
            $query->toSql(),
            'QueryBuilder should generate a SELECT query with WHERE, OR WHERE, LIMIT, and OFFSET clauses.'
        );

        $this->assertEquals(
            ['%John%', '%john@example.com%'],
            $query->getBindings(),
            'QueryBuilder should bind the search query values correctly.'
        );
    }
    
    public function testComplexConditions(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('age', '>=', 18)
            ->orWhere('country', 'LIKE', 'Sweden')
            ->whereNotNull('joined_at');

        $this->assertEquals(
            "SELECT * FROM `users` WHERE `age` >= ? OR `country` LIKE ? AND `joined_at` IS NOT NULL",
            $query->toSql()
        );

        $this->assertEquals(
            [18, 'Sweden'],
            $query->getBindings()
        );
    }

    public function testInsertQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->insert(['name' => 'John Doe', 'email' => 'john@example.com']);

        $this->assertEquals(
            "INSERT INTO `users` (`name`, `email`) VALUES (?, ?)",
            $query->toSql()
        );

        $this->assertEquals(
            ['John Doe', 'john@example.com'],
            $query->getBindings()
        );
    }

    public function testUpdateQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('id', '=', 1)
            ->update(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

        $this->assertEquals(
            "UPDATE `users` SET `name` = ?, `email` = ? WHERE `id` = ?",
            $query->toSql(),
            'QueryBuilder ska generera korrekt UPDATE-syntax.'
        );

        $this->assertEquals(
            ['Jane Doe', 'jane@example.com', 1],
            $query->getBindings(),
            'QueryBuilder ska korrekt hantera bindningsvärden för UPDATE med WHERE-villkor.'
        );
    }

    public function testDeleteQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('id', '=', 1)
            ->delete();

        $this->assertEquals(
            "DELETE FROM `users` WHERE `id` = ?",
            $query->toSql()
        );

        $this->assertEquals(
            [1],
            $query->getBindings()
        );
    }

    public function testDeleteQueryWithDuplicateBindings(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('id', '=', 1)
            ->where('id', '=', 1) // Dubblett
            ->delete();

        $this->assertEquals(
            'DELETE FROM `users` WHERE `id` = ? AND `id` = ?',
            $query->toSql()
        );

        $this->assertEquals(
            [1, 1],
            $query->getBindings(),
            'QueryBuilder ska hantera multiples bindningar korrekt, utan dubbelfiltering.'
        );
    }

    public function testUnionQueries(): void
    {
        $query1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('users')
            ->where('status', '=', 'active');

        $query2 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('archived_users')
            ->where('status', '=', 'active');

        $unionQuery = $query1->union($query2);

        $this->assertEquals(
            'SELECT `id`, `name` FROM `users` WHERE `status` = ? UNION SELECT `id`, `name` FROM `archived_users` WHERE `status` = ?',
            $unionQuery->toSql(),
            'QueryBuilder should generate correct UNION syntax.'
        );

        $this->assertEquals(
            ['active', 'active'],
            $unionQuery->getBindings(),
            'QueryBuilder should merge bindings correctly for UNION queries.'
        );
    }

    public function testSubqueries(): void
    {
        $subQuery = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id'])
            ->from('payments')
            ->where('amount', '>', 100);

        $mainQuery = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['name'])
            ->from('users')
            ->where('id', 'IN', $subQuery)
            ->where('status', '=', 'active');

        $this->assertEquals(
            'SELECT `name` FROM `users` WHERE `id` IN (SELECT `id` FROM `payments` WHERE `amount` > ?) AND `status` = ?',
            $mainQuery->toSql(),
            'QueryBuilder should handle subqueries correctly.'
        );

        $this->assertEquals(
            [100, 'active'],
            $mainQuery->getBindings(),
            'QueryBuilder should merge bindings correctly for subqueries.'
        );
    }

    public function testUnionAll(): void
    {
        $query1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('users')
            ->where('status', '=', 'active');

        $query2 = (new QueryBuilder())
            ->select(['id', 'name'])
            ->from('archived_users')
            ->where('status', '=', 'inactive');

        $unionAllQuery = $query1->union($query2, true);

        $this->assertEquals(
            'SELECT `id`, `name` FROM `users` WHERE `status` = ? UNION ALL SELECT `id`, `name` FROM `archived_users` WHERE `status` = ?',
            $unionAllQuery->toSql(),
            'QueryBuilder ska generera korrekt UNION ALL syntax.'
        );

        $this->assertEquals(
            ['active', 'inactive'],
            $unionAllQuery->getBindings(),
            'QueryBuilder ska hantera bindningar korrekt för UNION ALL.'
        );
    }

    public function testMultipleUnions(): void
    {
        $query1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('users')
            ->where('status', '=', 'active');

        $query2 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('archived_users')
            ->where('status', '=', 'inactive');

        $query3 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('deleted_users')
            ->where('status', '=', 'deleted');

        $unionQuery = $query1->union($query2)->union($query3);

        $this->assertEquals(
            'SELECT `id`, `name` FROM `users` WHERE `status` = ? UNION SELECT `id`, `name` FROM `archived_users` WHERE `status` = ? UNION SELECT `id`, `name` FROM `deleted_users` WHERE `status` = ?',
            $unionQuery->toSql(),
            'QueryBuilder ska stödja flera union-frågor.'
        );

        $this->assertEquals(
            ['active', 'inactive', 'deleted'],
            $unionQuery->getBindings(),
            'QueryBuilder ska korrekt hantera bindningar för flera union-frågor.'
        );
    }

    public function testUnionWithSubqueries(): void
    {
        $subQuery1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id'])
            ->from('payments')
            ->where('amount', '>', 100);

        $query1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('users')
            ->where('id', 'IN', $subQuery1);

        $query2 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('archived_users')
            ->where('status', '=', 'inactive');

        $unionQuery = $query1->union($query2);

        $this->assertEquals(
            'SELECT `id`, `name` FROM `users` WHERE `id` IN (SELECT `id` FROM `payments` WHERE `amount` > ?) UNION SELECT `id`, `name` FROM `archived_users` WHERE `status` = ?',
            $unionQuery->toSql(),
            'QueryBuilder ska hantera unioner korrekt med subqueries.'
        );

        $this->assertEquals(
            [100, 'inactive'],
            $unionQuery->getBindings(),
            'QueryBuilder ska hantera bindningar korrekt för unioner med subqueries.'
        );
    }

    public function testUnionWithInvalidType(): void
    {
        $this->expectException(\TypeError::class);

        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])->from('users');
        /** @var mixed $invalid */
        $invalid = 123; // avsiktligt fel typ
        $query->union($invalid);
    }

    public function testUnionWithoutBindings(): void
    {
        $query1 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('users');

        $query2 = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'name'])
            ->from('archived_users');

        $unionQuery = $query1->union($query2);

        $this->assertEquals(
            'SELECT `id`, `name` FROM `users` UNION SELECT `id`, `name` FROM `archived_users`',
            $unionQuery->toSql(),
            'QueryBuilder ska generera korrekt UNION utan bindningsvärden.'
        );

        $this->assertEquals(
            [],
            $unionQuery->getBindings(),
            'QueryBuilder ska hantera tomma bindningar korrekt för unioner.'
        );
    }

    public function testAggregateFunctions(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->count('*', 'total_users')
            ->max('age', 'oldest_user')
            ->avg('age', 'average_age');

        $this->assertEquals(
            'SELECT COUNT(*) AS `total_users`, MAX(`age`) AS `oldest_user`, AVG(`age`) AS `average_age` FROM `users`',
            $query->toSql(),
            'QueryBuilder ska generera korrekt SQL med flera aggregatfunktioner.'
        );

        $this->assertEquals([], $query->getBindings(), 'QueryBuilder ska ha noll bindningar för rena aggregatfunktioner.');
    }

    public function testSelectRaw(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->selectRaw("COUNT(id) AS total, NOW() AS current_time")
            ->from('users');

        $this->assertEquals(
            'SELECT COUNT(id) AS total, NOW() AS current_time FROM `users`',
            $query->toSql(),
            'QueryBuilder ska hantera raw SQL expressions korrekt i SELECT.'
        );
    }

    public function testAliasHandling(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['u.id AS user_id', 'u.name AS user_name'])
            ->from('users AS u')
            ->where('u.status', '=', 'active');

        $this->assertEquals(
            'SELECT `u`.`id` AS `user_id`, `u`.`name` AS `user_name` FROM `users` AS `u` WHERE `u`.`status` = ?',
            $query->toSql(),
            'QueryBuilder ska korrekt hantera alias till tabeller och kolumner.'
        );

        $this->assertEquals(['active'], $query->getBindings(), 'Bindningarna ska vara korrekt extraherade vid aliasering.');
    }

    public function testJoins(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->join('profiles', 'users.id', '=', 'profiles.user_id')
            ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
            ->fullJoin('addresses', 'users.id', '=', 'addresses.user_id');

        $this->assertEquals(
            'SELECT * FROM `users` INNER JOIN `profiles` ON `users`.`id` = `profiles`.`user_id` LEFT JOIN `orders` ON `users`.`id` = `orders`.`user_id` FULL OUTER JOIN `addresses` ON `users`.`id` = `addresses`.`user_id`',
            $query->toSql(),
            'QueryBuilder ska supporta olika typer av JOIN-klausuler.'
        );
    }

    public function testGroupByAndHaving(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['role', 'COUNT(*) AS total_employees'])
            ->from('users')
            ->groupBy('role')
            ->having('total_employees', '>', 10);

        $this->assertEquals(
            'SELECT `role`, COUNT(*) AS `total_employees` FROM `users` GROUP BY `role` HAVING `total_employees` > ?',
            $query->toSql(),
            'QueryBuilder ska generera korrekt SQL med GROUP BY och HAVING.'
        );

        $this->assertEquals([10], $query->getBindings());
    }

    public function testEmptyWhereClause(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')->where('', '=', 'active');
    }

    public function testInsertWithEmptyData(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new QueryBuilder())->from('users')->insert([]);
    }

    public function testTransactionRollback(): void
    {
        // Skapa en mockad Connection
        $mockConnection = $this->createMock(\Radix\Database\Connection::class);

        // Förvänta att transaktionsmetoderna kallas i rätt ordning
        $mockConnection->expects($this->once())
            ->method('beginTransaction');

        $mockConnection->expects($this->once())
            ->method('rollbackTransaction');

        $mockConnection->expects($this->never())
            ->method('commitTransaction'); // commit ska inte anropas vid rollback

        // Skapa en QueryBuilder och sätt mockad Connection
        $query = (new QueryBuilder())->setConnection($mockConnection);

        // Verifiera att undantag kastas (för rollback)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Simulated error");

        // Anropa metoden och simulera ett misslyckande
        $query->transaction(function () {
            throw new \Exception("Simulated error");
        });
    }

    public function testDistinctQuery(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->distinct()
            ->from('users')
            ->select(['name', 'email']);

        $this->assertEquals(
            'SELECT DISTINCT `name`, `email` FROM `users`',
            $query->toSql(),
            'QueryBuilder should generate a SQL query with DISTINCT keyword.'
        );
    }

    public function testConcatColumns(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->concat(['first_name', "' '", 'last_name'], 'full_name');

        $this->assertEquals(
            'SELECT CONCAT(`first_name`, \' \', `last_name`) AS `full_name` FROM `users`',
            $query->toSql(),
            'QueryBuilder should generate a SQL query using CONCAT for columns.'
        );
    }

    public function testWhereIn(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->whereIn('id', [1, 2, 3]);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `id` IN (?, ?, ?)',
            $query->toSql(),
            'QueryBuilder should generate a SQL query with WHERE IN clause.'
        );

        $this->assertEquals(
            [1, 2, 3],
            $query->getBindings(),
            'QueryBuilder should bind values correctly for WHERE IN clause.'
        );
    }

    public function testSubqueryInWhere(): void
    {
        $subQuery = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id'])
            ->from('payments')
            ->where('amount', '>', 100);

        $mainQuery = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('id', 'IN', $subQuery);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `id` IN (SELECT `id` FROM `payments` WHERE `amount` > ?)',
            $mainQuery->toSql(),
            'QueryBuilder should generate subquery in WHERE clause correctly.'
        );

        $this->assertEquals(
            [100],
            $mainQuery->getBindings(),
            'QueryBuilder should bind subquery values correctly.'
        );
    }

    public function testLimitAndOffset(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->where('status', '=', 'active')
            ->orderBy('name')
            ->limit(10)
            ->offset(20);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `status` = ? ORDER BY `name` ASC LIMIT 10 OFFSET 20',
            $query->toSql(),
            'QueryBuilder should generate correct LIMIT and OFFSET clauses.'
        );

        $this->assertEquals(
            ['active'],
            $query->getBindings(),
            'QueryBuilder should bind values correctly for paginated queries.'
        );
    }

    public function testDeleteWithoutWhereThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("DELETE operation requires a WHERE clause.");

        $query = (new QueryBuilder())->from('users')->delete();
    }

    public function testWrapColumn(): void
    {
        $query = (new QueryBuilder())->setConnection($this->connection);
        $this->assertEquals('`users`.`id`', $query->testWrapColumn('users.id'));
        $this->assertEquals('`name`', $query->testWrapColumn('name'));
        $this->assertEquals('COUNT(*)', $query->testWrapColumn('COUNT(*)'));
    }

    public function testWrapAlias(): void
    {
        $query = (new QueryBuilder())->setConnection($this->connection);
        $this->assertEquals('`user_count`', $query->testWrapAlias('user_count'));
        $this->assertEquals('`count`', $query->testWrapAlias('count'));
    }

    public function testUpperFunction(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->upper('name', 'upper_name');

        $this->assertEquals(
            'SELECT UPPER(`name`) AS `upper_name` FROM `users`',
            $query->toSql(),
            'QueryBuilder should generate correct SQL for UPPER function.'
        );
    }

    public function testYearFunction(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('orders')
            ->year('created_at', 'order_year');

        $this->assertEquals(
            'SELECT YEAR(`created_at`) AS `order_year` FROM `orders`',
            $query->toSql(),
            'QueryBuilder should generate correct SQL for YEAR function.'
        );
    }

    public function testMonthFunction(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('orders')
            ->month('created_at', 'order_month');

        $this->assertEquals(
            'SELECT MONTH(`created_at`) AS `order_month` FROM `orders`',
            $query->toSql(),
            'QueryBuilder should generate correct SQL for MONTH function.'
        );
    }

    public function testDateFunction(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('orders')
            ->date('created_at', 'order_date');

        $this->assertEquals(
            'SELECT DATE(`created_at`) AS `order_date` FROM `orders`',
            $query->toSql(),
            'QueryBuilder should generate correct SQL for DATE function.'
        );
    }

    public function testJoinSubQuery(): void
    {
        $subQuery = (new QueryBuilder())
            ->setConnection($this->connection)
            ->select(['id', 'user_id'])
            ->from('orders')
            ->where('status', '=', 'completed');

        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->joinSub($subQuery, 'completed_orders', 'users.id', '=', 'completed_orders.user_id');

        $this->assertEquals(
            'SELECT * FROM `users` INNER JOIN (SELECT `id`, `user_id` FROM `orders` WHERE `status` = ?) AS `completed_orders` ON `users`.`id` = `completed_orders`.`user_id`',
            $query->toSql(),
            'QueryBuilder should generate correct SQL for joinSub with a subquery.'
        );

        $this->assertEquals(
            ['completed'],
            $query->getBindings(),
            'QueryBuilder should bind values correctly for joinSub.'
        );
    }

    public function testWhereLike(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->whereLike('name', '%John%');

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `name` LIKE ?',
            $query->toSql(),
            'QueryBuilder ska generera en SQL-sökfråga med WHERE LIKE-klausul.'
        );

        $this->assertEquals(
            ['%John%'],
            $query->getBindings(),
            'QueryBuilder ska korrekt binda värden för WHERE LIKE-klausul.'
        );
    }

    public function testWhereLikeWithMultipleConditions(): void
    {
        $query = (new QueryBuilder())
            ->setConnection($this->connection)
            ->from('users')
            ->whereLike('email', 'john%@example.com')
            ->orWhere('status', '=', 'active');

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `email` LIKE ? OR `status` = ?',
            $query->toSql(),
            'QueryBuilder ska korrekt kombinera WHERE LIKE och OR.'
        );

        $this->assertEquals(
            ['john%@example.com', 'active'],
            $query->getBindings(),
            'QueryBuilder ska korrekt hantera bindningar med flera villkor.'
        );
    }
}