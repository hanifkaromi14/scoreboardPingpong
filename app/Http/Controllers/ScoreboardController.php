<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ScoreboardController extends Controller
{
    public function index(Request $request)
    {
        $data = session('score', [
            'red' => ['game' => 0, 'set' => 0],
            'blue' => ['game' => 0, 'set' => 0],
            'mode' => 'bo3',
            'team_name' => ['red' => 'Merah', 'blue' => 'Biru'],
            'set_scores' => []
        ]);

        return view('scoreboard', compact('data'));
    }

    public function ajaxUpdate(Request $request)
    {
        $action = $request->input('action');
        $mode = $request->input('mode', 'bo3');

        $data = session('score', [
            'red' => ['game' => 0, 'set' => 0],
            'blue' => ['game' => 0, 'set' => 0],
            'mode' => $mode,
            'team_name' => ['red' => 'Merah', 'blue' => 'Biru'],
            'set_scores' => []
        ]);

        $data['mode'] = $mode;

        if ($request->has('team_name')) {
            $data['team_name'] = array_merge($data['team_name'], $request->input('team_name'));
        }

        if (str_ends_with($action, '_add')) {
            $team = str_replace('_add', '', $action);
            $data[$team]['game'] += 1;
        } elseif (str_ends_with($action, '_sub')) {
            $team = str_replace('_sub', '', $action);
            $data[$team]['game'] = max(0, $data[$team]['game'] - 1);
        }

        $r = $data['red']['game'];
        $b = $data['blue']['game'];
        $last_set = null;

        if (($r >= 11 && $r - $b >= 2) || ($r >= 10 && $b >= 10 && $r - $b >= 2)) {
            $data['red']['set'] += 1;
            $last_set = ['red' => $r, 'blue' => $b];
            $data['set_scores'][] = $last_set;
            $data['red']['game'] = 0;
            $data['blue']['game'] = 0;
        } elseif (($b >= 11 && $b - $r >= 2) || ($b >= 10 && $r >= 10 && $b - $r >= 2)) {
            $data['blue']['set'] += 1;
            $last_set = ['red' => $r, 'blue' => $b];
            $data['set_scores'][] = $last_set;
            $data['red']['game'] = 0;
            $data['blue']['game'] = 0;
        }

        session(['score' => $data]);

        return response()->json(array_merge($data, ['last_set' => $last_set]));
    }

    public function ajaxReset()
    {
        session()->forget('score');
        return response()->json(['status' => 'reset']);
    }
}
