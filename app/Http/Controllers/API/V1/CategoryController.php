<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CreateRequest;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{

    public function index()
    {
        $result = DB::table('categories');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('name', "like", "%{$search}%");
        }
        if ((request()->input("sortby")!=null) && in_array(request()->input("sortby"), ['id', 'created_at'])) {
            $sortBy=request()->input("sortby");
        }else{
            $sortBy='id';
        }
        if ((request()->input("sortorder")!=null) && in_array(request()->input("sortorder"), ['asc', 'desc'])) {
            $sortOrder=request()->input("sortorder");
        }else{
            $sortOrder='desc';
        }
        if (!empty(request()->input("perpage"))) {
            $perPage=request()->input("perpage");
        } else {
            $perPage=10;
        }

        $data=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($data, 200);
    }

    public function store(CreateRequest $request)
    {
        $query=Category::where('name', "like", "%{$request->name}%")->first();
        if ($query) {
            return response()->json(["message" => 'Record Already exist.', "status" => "error"], 400);
        }
        $category = Category::create([
            'name' => $request->name,
        ]);

        $response=[
            "message" => "Category Created Successfully",
            'category' => $category,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $category=Category::find($id);
        if (!$category) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Category found",
            'cat' => $category,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function update(CreateRequest $request, Category $category)
    {
        $query=Category::where('name', "like", "%{$request->name}%")->
        where('id', '!=', $category->id)->first();
        if ($query) {
            return response()->json(["message" => 'Record Already exist.', "status" => "error"], 400);
        }
        $category->update([
            'name' => $request->name,
        ]);
        $response=[
            "message" => "Category Updated Successfully",
            'category' => $category,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        $category->delete();
        $response=[
            "message" => "Category Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
