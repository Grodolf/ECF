<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\MongoDBConnection;

class StatsModel
{
    private \MongoDB\Collection $collection;

    public function __construct()
    {
        $this->collection = MongoDBConnection::getInstance()->selectCollection('sales');
    }

    public function getAvailableYears(): array
    {
        return $this->collection->distinct('year');
    }

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
