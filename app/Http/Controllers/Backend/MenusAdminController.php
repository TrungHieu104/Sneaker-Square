<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MenuModel;
use App\Http\Requests\Backend\MenuRequest;
use App\Http\Requests\Backend\MenuUpRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
class MenusAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        View::share(compact('keyword'));
    }
    public function index(Request $request)
    {
        $perpage = 15;
        $orderBy = $request->input('sort-by', 'menu_id'); 
        $orderType = $request->input('sort-type', 'asc');


        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['menu_name'];
        
        $menu = $this->performSearch(MenuModel::orderBy($orderBy, $orderType), $keyword, $searchableFields)
        ->paginate($perpage)
        ->withQueryString();

        
        return view('backend.pages.menus.menus_list', compact('menu','orderBy', 'orderType'));
    }

    public function status(Request $request, $id)
    {
        $arr = $request->post();
        $status = ($request->has('m-status'))? $arr['m-status']:"";
        $data = MenuModel::find($id);

        if (!$data) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], 404);
        }

        $data->menu_hidden = $status;
        $data->save();

        return response()->json(['message' => 'Cập nhật thành công']);
        
    }

    function menu_tree($data, $parent_id = 0, $level=0){
        $result = [];
        foreach($data as $item){
            if($item['menu_parent_id'] == $parent_id){
                $item['level'] = $level;
                $result[] = $item;
                $child = $this->menu_tree($data, $item['menu_id'], $level+1);
                $result = array_merge($result, $child);
            }
        }
        return $result;
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data = MenuModel::where('menu_hidden',1)->orderBy('menu_position','asc')->get();
        $menu = $this->menu_tree($data);
        return view('backend.pages.menus.menus_create', compact('menu'));
    }

    public function store(MenuRequest $request)
    {
        $arr = $request->post();
        $name = ($request->has('name'))? $arr['name']:"";
        $slug = ($request->has('slug'))? $arr['slug']:"";
        $parent_id = ($request->has('parent_id'))? $arr['parent_id']:"";
        $position = ($request->has('position'))? $arr['position']:"";
        $status = ($request->has('status'))? (int)$arr['status']:"0";
        $menu = new MenuModel;
        $menu->menu_name = $name;
        $menu->menu_link = $slug;
        $menu->menu_parent_id = $parent_id;
        $menu->menu_position = $position;
        $menu->menu_hidden = $status;
        $menu->save();
        Session::flash('iconMessage', 'success');
        return redirect('admin/menus')->with('message', 'Thêm thành công');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request,string $menu_id)
    {
        $menus = MenuModel::find($menu_id);
        if ($menus==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/menus')->with('message', 'Không tồn tại menu');
        }
        $data = MenuModel::orderBy('menu_position','asc')->get();
        $menu = $this->menu_tree($data);
        return view("backend.pages.menus.menus_edit", compact('menus','menu'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MenuUpRequest $request, string $menu_id)
    {
        $arr = $request->post();
        $name = ($request->has('name'))? $arr['name']:"";
        $slug = ($request->has('slug'))? $arr['slug']:"";
        $parent_id = ($request->has('parent_id'))? $arr['parent_id']:"";
        $position = ($request->has('position'))? $arr['position']:"";
        $status = ($request->has('status'))? (int)$arr['status']:"0";
        $menu = MenuModel::find($menu_id);
        if ($menu ==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/faq')->with('message', 'Không tồn tại menu');;
        }
        $menu->menu_name = $name;
        $menu->menu_link = $slug;
        $menu->menu_parent_id = $parent_id;
        $menu->menu_position = $position;
        $menu->menu_hidden = $status;
        $menu->save();
        Session::flash('iconMessage', 'success');
        return redirect('admin/menus')->with('message', 'Chỉnh sửa thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(Request $request,string $id)
    // {
    //     $menu = MenuModel::find($id);
    //     if ($menu==null) {
    //         $request->session();
    //         Session::flash('iconMessage', 'info');
    //         redirect()->back()->with('message', 'Không tồn tại thông tin!');
    //     }
    //     if ($menu->menu_parent_id == null && $menu->children()->count() > 0) {
    //         Session::flash('iconMessage', 'info');
    //         return redirect()->back()->with('message', 'Không thể xóa menu cha!');
    //     }
    //     $menu->delete();
    //     Session::flash('iconMessage', 'success');
    //     return redirect('/admin/menus')->with('message', 'Xóa thành công!');
    // }
    public function softDelete(Request $request, string $id)
    {
        $menu = MenuModel::find($id);
        if ($menu == null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect()->back()->with('message', 'Không tồn tại thông tin!');
        }
        if ($menu->menu_parent_id == null && $menu->children()->count() > 0) {
            Session::flash('iconMessage', 'error');
            return redirect()->back()->with('message', 'Không thể xóa menu cha!');
        }
        $menu->delete();
        Session::flash('iconMessage', 'success');
        return redirect('/admin/menus')->with('message', 'Xóa thành công!');
    }


    public function trashed(Request $request){
        $perpages = 10;
        $keyword = $request->input('keyword');
        $searchableFields = ['menu_name'];
        
        $menuTrash = $this->performSearch(MenuModel::onlyTrashed(), $keyword, $searchableFields)
        ->paginate($perpages)
        ->withQueryString();
        return view('backend.pages.menus.menus_trash', compact('menuTrash','keyword'));
    }

    public function restore($id){
        $menuRe = MenuModel::withTrashed()->where('menu_id', $id)->first();
        if ($menuRe) {
            $menuRe->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Hoàn tác thành công!');
        } else {
            return abort(404);
        }
    }

    public function restoreAll() {
        $trashed=MenuModel::onlyTrashed();
        if ($trashed->count() > 0) {
            $trashed->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Hoàn tác thành công!');
        } else {
            Session::flash('iconMessage', 'info');
            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }
    
    public function forceDelete($id){
        $menuDe = MenuModel::withTrashed()->find($id); // Lấy bản ghi đã xóa mềm
        if ($menuDe) {
            $menuDe->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa thành công!');
        } else {
            return abort(404); 
        }
    }

    public function deleteAll() 
    {
        $trashAll = MenuModel::onlyTrashed()->get();
        if ($trashAll->count() > 0) {
            MenuModel::onlyTrashed()->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa menu thành công!');
        } else {
            Session::flash('iconMessage', 'info');
            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }
}
