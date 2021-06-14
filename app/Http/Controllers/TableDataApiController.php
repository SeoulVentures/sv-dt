<?php

namespace App\Http\Controllers;

use App\Models\Query;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableDataApiController extends Controller
{
    function read(Request $request) {
        $options = $request->all([
            'page',
            'perPage',
            'sortColumn',
            'sortAscending',
            'queryId',
            'filters'
        ]);

        $m = Query::find($options['queryId']);
        if(!$m) return response()->json([
            'result' => false
        ]);

        $page = intval($options['page'] ?? 1);
        $perPage = intval($options['perPage'] ?? 50);

        $contents = collect(DB::select($m->query));
        if($options['sortColumn']) {
            $contents = $contents->sortBy([
                [ $options['sortColumn'], $options['sortAscending'] === 'false' ? 'desc' : 'asc' ]
            ]);
        }
        if(!empty($options['filters'])) {
            $filters = json_decode($options['filters']);
            foreach($filters as $column => $rule) {
                $contents = $contents->filter(function($row) use ($column, $rule) {
                    switch($rule->code) {
                        case 'eq':
                            return $row->{$column} == $rule->value;
                            break;
                        case 'lt':
                            return $row->{$column} < $rule->value;
                            break;
                        case 'gt':
                            return $row->{$column} > $rule->value;
                            break;
                        case 'lte':
                            return $row->{$column} <= $rule->value;
                            break;
                        case 'gte':
                            return $row->{$column} >= $rule->value;
                            break;
                        case 'ne':
                            return $row->{$column} != $rule->value;
                            break;
                        case 'contain':
                            return Str::contains($row->{$column}, $rule->value);
                            break;
                        case 'start':
                            return Str::startsWith($row->{$column}, $rule->value);
                            break;
                        case 'end':
                            return Str::endsWith($row->{$column}, $rule->value);
                            break;
                    }
                });
            }
        }

        $count = $contents->count();
        $contents = $contents->slice(($page - 1) * $perPage, $perPage)->values()->toArray();
        $contents = array_map(function($row) {
            $keys = array_keys(get_object_vars($row));
            foreach($keys as $key) $row->{$key} = htmlentities(trim($row->{$key}));
            return $row;
        }, $contents);

        return response()->json([
            'result' => true,
            'data' => [
                'contents' => $contents,
                'pagination' => [
                    'page' => $page,
                    'totalCount' => $count
                ]
            ]
        ]);
    }

    function headers(Request $request) {
        $options = $request->all([
            'queryId'
        ]);

        $m = Query::find($options['queryId']);
        if(!$m) return response()->json([]);

        $content = DB::select($m->query)[0];
        if(!$content) return response()->json([]);

        $data = get_object_vars($content);
        return response()->json(array_map(fn($row) => [
            'header' => Str::title(Str::replace('_', ' ', $row)),
            'name' => $row,
            'filter' => [
                'type' => is_integer($data[$row]) ? 'number' : 'text',
                'showApplyBtn' => true,
                'showClearBtn' => true
            ],
            'sortable' => true
        ], array_keys($data)));
    }
}
