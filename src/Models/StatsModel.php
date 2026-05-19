<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\MongoDBConnection;

/**
 * Data-access layer for sales statistics stored in MongoDB.
 *
 * All queries target the 'sales' collection, where each document represents
 * a monthly sales summary with a nested 'sales' array of individual order records.
 */
class StatsModel
{
    private \MongoDB\Collection $collection;

    public function __construct()
    {
        $this->collection = MongoDBConnection::getInstance()->selectCollection('sales');
    }

    /**
     * Returns all distinct years present in the sales collection.
     *
     * @return array List of year values (int).
     */
    public function getAvailableYears(): array
    {
        return $this->collection->distinct('year');
    }

    /**
     * Returns total order count and revenue grouped by menu, across all time.
     *
     * @return array Aggregated rows with _id (menu_id), title, total_sales, total_price.
     */
    public function getOrdersByMenu(): array
    {
        $pipeline = [
            ['$unwind' => '$sales'],
            ['$group' => [
                '_id'         => '$sales.menu_id',
                'title'       => ['$first' => '$sales.title'],
                'total_sales' => ['$sum' => 1],
                'total_price' => ['$sum' => '$sales.total_price']
            ]]
        ];
        $orders = $this->collection->aggregate($pipeline);

        return $orders->toArray();
    }

    /**
     * Returns total order count and revenue grouped by menu for a given date range.
     *
     * When year_start equals year_end the query filters on month range within that year.
     * When the years differ it matches documents from month_start of year_start through
     * month_end of year_end. An optional menu_id filter further restricts results.
     *
     * @param array $filters Keys: year_start, year_end, month_start, month_end, menu_id (optional).
     * @return array Aggregated rows with _id (menu_id), title, total_sales, total_price.
     */
    public function getRevenueByMenu(array $filters): array
    {
        $pipeline = [];
        if ($filters['year_start'] === $filters['year_end']) {
            $pipeline[] = [
                '$match' => [
                'year'  => $filters['year_start'],
                'month' => [
                    '$gte' => $filters['month_start'],
                    '$lte' => $filters['month_end']
                ]
                ]
            ];
        } else {
            $pipeline[] = [
               '$match' => [
                   '$or' => [
                       ['year' => $filters['year_start'], 'month' => ['$gte' => $filters['month_start']]],
                       ['year' => $filters['year_end'], 'month' => ['$lte' => $filters['month_end']]]
                   ]
               ]
            ];
        }

        $pipeline[] = ['$unwind' => '$sales'];

        if (!empty($filters['menu_id'])) {
            $pipeline[] = ['$match' => ['sales.menu_id' => (int)$filters['menu_id']]];
        }

        $pipeline[] = [
                '$group' => [
                    '_id'         => '$sales.menu_id',
                    'title'       => ['$first' => '$sales.title'],
                    'total_sales' => ['$sum' => 1],
                    'total_price' => ['$sum' => '$sales.total_price']
            ]];

        $revenue = $this->collection->aggregate($pipeline);

        return $revenue->toArray();
    }
}
