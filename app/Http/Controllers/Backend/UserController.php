<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use App\Repositories\Backend\User\UserContract;
use App\Repositories\Backend\Role\RoleContract;
use App\Repositories\Backend\Permission\PermissionContract;

class UserController extends BaseController
{

    /**
     * @var UserContract
     */
    protected $users;

    /**
     * @var RoleContract
     */
    protected $roles;

    /**
     * @var PermissionContract
     */
    protected $permissions;


    /**
     * @param UserContract       $users
     * @param RoleContract       $roles
     * @param PermissionContract $permissions
     */
    public function __construct(UserContract $users, RoleContract $roles, PermissionContract $permissions)
    {
        $this->users = $users;
        $this->roles = $roles;
        $this->permissions = $permissions;
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $field = Input::get('sortField') ?: 'id';
            $sorter = str_replace('end', '',Input::get('sortOrder')) ?: 'desc';
            $users = $this->users->getAllUsers(Input::get('pageSize'), $field, $sorter);
            foreach ($users as &$user) {
                $user['roles'] = self::formatToTree($user->roles);
            }
            $users = $users->toArray();
            $result['data']['user']['total'] = $users['total'];
            $result['data']['users']['users'] = $users['data'];
            $result['data']['roles'] = $this->roles->getAllRoles();
            return response()->json($result);
        }
        return view('backend.user.index');
    }

    private static function formatToTree($arr)
    {
        $result = array();
        if ($arr) {
            foreach ($arr as $key => $item) {
                $result[$key]['label'] = $item->role_name;
                $result[$key]['value'] = $item->id;
                $result[$key]['key'] = $item->id;
            }
        }
        return $result;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('backend.user.create',
            [
                'roles' => $this->roles->getAllRoles('id', 'desc', true),
                'userRoles' => array()
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        if ($this->users->create($request->except('assignees_roles'), $request->only('assignees_roles'))) {
            return redirect()->route('admin.auth.user.index');
        }
        return Redirect::back()->withInput()->withErrors('保存失败！');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $user = $this->users->find($id);

        return view(
            'backend.user.edit',
            [
                'user' => $user,
                'userRoles' => $user->roles->lists('id')->all(),
                'roles' => $this->roles->getAllRoles('id', 'desc', true)
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int    $id
     * @param Request $request
     * @return Response
     */
    public function update($id, Request $request)
    {
        try{
            $statusCode = 200;
            $response = [
                'status' => $statusCode,
                'message'  => 'ok'
            ];

            $this->users->update($id, $request->except('assignees_roles'), $request->only('assignees_roles'));

        }catch (\Exception $e){
            $statusCode = 400;
            $response = [
                'status' => $statusCode,
                "message" => $e->getMessage()
            ];
        }finally{
            return response()->json($response, $statusCode);
        }
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function changePassword($id, Request $request) {
        return view('backend.user.change-password')
            ->withUser($this->users->find($id));
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function updatePassword($id, Request $request) {
        $this->users->updatePassword($id, $request->all());
        return redirect()->route('admin.auth.user.index')->withFlashSuccess(trans("alerts.users.updated_password"));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        try{
            $statusCode = 200;
            $response = [
                'message'  => 'ok'
            ];

            $this->users->destroy($id);

        }catch (\Exception $e){
            $statusCode = 400;
            $response = [
                "error" => $e->getMessage()
            ];
        }finally{
            return response()->json($response, $statusCode);
        }
    }
}
