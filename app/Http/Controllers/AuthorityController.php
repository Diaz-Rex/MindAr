<?php

namespace App\Http\Controllers;

use App\Models\AuthorityModel;
use App\Models\UrlModel;
use App\Models\User;
use Illuminate\Http\Request;

class AuthorityController extends Controller
{
    public function authority(Request $request)
    {
        if ($request->isMethod('post') && $request->type === 'link') {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $linkName = trim($validated['name']);
            $linkName = $linkName === '/' ? '/' : trim($linkName, '/');

            if ($linkName === '') {
                return back()->withErrors([
                    'name' => 'Enter a valid link name.',
                ]);
            }

            UrlModel::firstOrCreate(['linkName' => $linkName]);

            return back()->with('success', 'Permission added.');
        }

        if ($request->isMethod('post') && $request->type === 'add_access') {
            $validated = $request->validate([
                'id' => 'required|exists:users,id',
                'linkName_id' => 'required|exists:table_urls,id',
            ]);

            AuthorityModel::firstOrCreate([
                'user_id' => $validated['id'],
                'linkName_id' => $validated['linkName_id'],
            ]);

            return back()->with('success', 'Access added.');
        }

        if ($request->isMethod('post') && $request->type === 'remove_access') {
            $validated = $request->validate([
                'id' => 'required|exists:users,id',
                'linkName_id' => 'required|exists:table_urls,id',
            ]);

            AuthorityModel::where('user_id', $validated['id'])
                ->where('linkName_id', $validated['linkName_id'])
                ->delete();

            return back()->with('success', 'Access removed.');
        }

        $data = UrlModel::where('linkName', '/')
            ->orWhere('linkName', 'not like', '/%')
            ->orderBy('linkName')
            ->get();

        $users = User::with(['permissions' => function ($query) {
            $query->where(function ($permissionQuery) {
                $permissionQuery->where('linkName', '/')
                    ->orWhere('linkName', 'not like', '/%');
            })
                ->orderBy('linkName');
        }])
            ->orderBy('name')
            ->get();

        return view('Pages.authority.index', compact('data', 'users'));
    }
}
