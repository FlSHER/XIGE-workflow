<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Admin\ValidatorRequest;
use App\Models\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ValidatorController extends Controller
{
    /**
     * 验证规则新增保存
     * @param ValidatorRequest $request
     */
    public function store(ValidatorRequest $request)
    {
        $data = Validator::create($request->input());
        return app('apiResponse')->post($data);
    }

    /**
     * 验证规则编辑保存
     * @param ValidatorRequest $request
     * @return mixed
     */
    public function update(ValidatorRequest $request,Validator $validator)
    {
        $validator->update($request->input());
        return app('apiResponse')->put($validator);
    }

    /**
     * 验证规则删除
     * @param Request $request
     * @return mixed
     */
    public function destroy(Validator $validator)
    {
        if ($validator->fields->count() > 0)
            abort(403,'该验证规则已经被使用了,不能进行删除');
        $validator->delete();
        return app('apiResponse')->delete();
    }

    /**
     * 规则列表
     * @param Request $request
     */
    public function index()
    {
        $data = Validator::orderBy('id','desc')->get();
        return app('apiResponse')->get($data);
    }

    /**
     * 规则编辑获取数据
     * @param Request $request
     */
    public function show(Validator $validator)
    {
        return app('apiResponse')->get($validator);
    }
}
