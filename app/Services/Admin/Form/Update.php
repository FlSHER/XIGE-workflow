<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/1/001
 * Time: 11:19
 */

namespace App\Services\Admin\Form;


use App\Models\Field;
use App\Models\Flow;
use App\Models\Form;
use App\Models\FormGrid;
use Illuminate\Database\Eloquent\Collection;

trait Update
{
    public function updateForm($request)
    {
        $formDataTable = new FormDataTableService($request->id);
        $form = Form::findOrFail($request->id);
        if ($formDataTable->getFormDataCount() > 0) {
            //表单数据表含有数据
            $form = $this->formDataIsTrueUpdate($form);
        } else {
            //表单数据表无数据
            $form = $this->formDataIsNullUpdateSave($form);
        }
        $form->fieldGroups()->delete();
        // 字段分组保存
        $form->fieldGroups()->createMany($request->input('field_groups'));

        $this->updateStepFieldsKey($request);//修改步骤字段 (可以、必填、编辑、隐藏字段)
        $data = Form::with([
            'fields' => function ($query) {
                $query->whereNull('form_grid_id')->orderBy('sort', 'asc');
            },
            'fields.validator',
            'grids.fields.validator'
        ])->find($form->id);
        return $data;
    }

    /*------------------------------------------表单data表有数据start--------------------------------------*/

    protected function formDataIsTrueUpdate($form)
    {
        //比较表单字段
        $formFieldUpdate = (bool)$this->formFieldsDiff($form->formFields);
        $gridFieldUpdate = $this->gridDiff($form->grids);
        //判断表单、控件字段改动
        if ($formFieldUpdate && $gridFieldUpdate) {
            //修改当前数据
            //表单修改
            $form->update(request()->input());
            //表单字段修改
            $this->formDataIsTrueFormFieldsUpdate($form);
            //控件字段修改
            $this->formDataIsTrueGridUpdate($form);
        } else {
            //新增全部数据
            $oldForm = $form;
            $form->delete();
            $form = $this->create(request());//重新插入新数据
            //表单编号添加
            $form->number = $oldForm->number;
            $form->save();
            Flow::where('form_id', request()->route('id'))->update(['form_id' => $form->id]);//修改流程表的表单id
        }
        return $form;
    }

    /**
     * 比较表单字段
     * @param object $fields
     * @return bool
     */
    protected function formFieldsDiff(object $fields)
    {
        $requestFields = request()->input('fields');
        $requestIds = array_pluck($requestFields, 'id');
        //去除空值
        $requestIds = array_filter($requestIds);
        $dbIds = $fields->pluck('id')->all();
        if (array_diff($dbIds, $requestIds)) {
            //request 提交的字段少了
            return false;
        }
        return $this->dbFieldAndRequestFieldDiff($requestFields, $fields);
    }

    /**
     * 验证表单字段值是否有改动
     * @param array $requestFields
     * @param object $dbFields
     * @return bool
     */
    protected function dbFieldAndRequestFieldDiff(array $requestFields, object $dbFields)
    {
        $fieldsKeyBy = $dbFields->keyBy('id');
        foreach ($requestFields as $field) {
            if (array_has($field, 'id') && $field['id']) {
                $db = $fieldsKeyBy[$field['id']];
                if ($db->key != $field['key'] ||
                    $db->type != $field['type'] ||
                    $db->is_checkbox != $field['is_checkbox'] ||
                    $db->region_level != $field['region_level'] ||
                    $db->min != $field['min'] ||
                    $db->max != $field['max'] ||
                    $db->scale != $field['scale'] ||
                    array_diff($db->options, $field['options'])
                ) {
                    return false;
                    break;
                }
            }
        }
        return true;
    }

    /**
     *比较控件
     * @param object $dbGrids
     */
    protected function gridDiff(object $dbGrids)
    {
        if ($dbGrids->count() > 0) {
            $check = $this->dbGridAndRequestGridDiff($dbGrids);
            if ($check == false) {
                return false;
            }
        }
        return true;
    }

    protected function dbGridAndRequestGridDiff($dbGrids)
    {
        $rqGrids = request()->input('grids', []);
        if (count($rqGrids) == 0) {
            return false;
        }
        $rqGridIds = array_pluck($rqGrids, 'id');
        //去除空值
        $rqGridIds = array_filter($rqGridIds);
        $dbGridIds = $dbGrids->pluck('id')->all();
        if (array_diff($dbGridIds, $rqGridIds)) {
            //数据库控件多于request控件
            return false;
        }

        //request控件大于或等于数据库控件

        //比较控件data
        $checkGrid = $this->checkGridDiff($rqGrids, $dbGrids);
        if ($checkGrid == false) {
            return false;
        }
        return $this->checkGridFieldsDiff($rqGrids, $dbGrids);
//        return true;
    }

    protected function checkGridDiff(array $rqGrids, object $dbGrids)
    {
        $rqGridArray = [];
        foreach ($rqGrids as $grid) {
            if (array_has($grid, 'id') && $grid['id']) {
                array_push($rqGridArray, $grid['id'] . $grid['key']);
            }
        }
        $dbGridArray = $dbGrids->map(function ($grid) {
            return $grid->id . $grid->key;
        })->all();

        if (array_diff($dbGridArray, $rqGridArray)) {
            return false;
        }
        return true;
    }

    protected function checkGridFieldsDiff(array $rqGrids, object $dbGrids)
    {
        $dbGridsKeyBy = $dbGrids->keyBy('id');
        foreach ($rqGrids as $grid) {
            $rqFieldIds = array_pluck($grid['fields'], 'id');
            $rqFieldIds = array_filter($rqFieldIds);
            $dbFieldIds = $dbGridsKeyBy[$grid['id']]->fields->pluck('id')->all();
            if (array_diff($dbFieldIds, $rqFieldIds)) {
                return false;
                break;
            }
            $check = $this->dbFieldAndRequestFieldDiff($grid['fields'], $dbGridsKeyBy[$grid['id']]->fields);
            if ($check == false) {
                return false;
                break;
            }
        }
        return true;
    }

    /**
     * 表单字段修改（formData有数据）
     * @param $form
     */
    protected function formDataIsTrueFormFieldsUpdate($form)
    {
        $formDataTable = new FormDataTableService($form->id);

        foreach (request()->input('fields') as $k => $v) {
            $v['sort'] = $k;
            if (array_has($v, 'id') && $v['id']) {
                //编辑
                $field = Field::find($v['id']);
                $field->update($v);
                $field->validator()->sync(array_get($v, 'validator_id'));
                if ($field->widgets->count() > 0) {
                    $field->widgets()->delete();
                }
                //修改form_data表注释
                $formDataTable->saveFormFieldTableComment($field->toArray());
            } else {
                //新增
                $v['form_id'] = $form->id;
                $field = Field::create($v);
                $field->validator()->sync(array_get($v, 'validator_id'));
                //表单data表添加字段
                $formDataTable->saveFormFieldTable($field->toArray());
            }
            //员工、部门、店铺ID数据控件数据新增
            if (array_has($v, 'available_options') && $v['available_options']) {
                data_fill($v['available_options'], '*.field_id', $field->id);
                $field->widgets()->createMany($v['available_options']);
            }
        }
    }

    /**
     * 控件字段修改（formData有数据）
     * @param $form
     */
    protected function formDataIsTrueGridUpdate($form)
    {
        $rqGrids = request()->input('grids', []);
        if ($rqGrids) {
            foreach ($rqGrids as $k => $grid) {
                if (array_has($grid, 'id') && $grid['id']) {
                    //编辑
                    $formGrid = FormGrid::find($grid['id']);
                    //表单控件数据修改
                    $formGrid->update($grid);
                    //控件字段修改
                    $this->formDataIsTrueGridFieldsUpdate($grid['fields'], $formGrid);
                } else {
                    //新增
                    $grid['form_id'] = $form->id;
                    $this->gridItemSave($grid);
                }
            }
        }
    }

    protected function formDataIsTrueGridFieldsUpdate(array $fields, $formGrid)
    {
        foreach ($fields as $k => $field) {
            $field['sort'] = $k;
            if (array_has($field, 'id') && $field['id']) {
                //编辑
                $fieldData = Field::find($field['id']);
                $fieldData->update($field);
                $fieldData->validator()->sync($field['validator_id']);
                //部门、员工、店铺控件
                if ($fieldData->widgets->count() > 0) {
                    $fieldData->widgets()->delete();
                }
                if (array_has($field, 'available_options') && $field['available_options']) {
                    data_fill($field['available_options'], '*.field_id', $fieldData->id);
                    $fieldData->widgets()->createMany($field['available_options']);
                }
                $data['key'] = $formGrid->key;
                $data['field'] = $fieldData->toArray();
                $formDataTable = new FormDataTableService($formGrid->form_id);
                $formDataTable->saveFormGridTableComment($data);
            } else {
                //新增
                $field['form_id'] =$formGrid->form_id;
                $field['form_grid_id'] =$formGrid->id;
                $fieldData = (array)$this->fieldsItemSave($field);//新增控件字段
                $data['key'] = $formGrid->key;
                $data['field'] = $fieldData;
                $formDataTable = new FormDataTableService($formGrid->form_id);
                $formDataTable->saveFormGridTable($data);//创建列表控件表
            }
        }
    }

    /*------------------------------------------表单data表有数据end--------------------------------------*/


    /*-------------------------------表单data无数据修改start-----------------------------------------------*/
    /**
     * 表单数据表无数据进行编辑保存
     * @param $form
     */
    protected function formDataIsNullUpdateSave($form)
    {
        //表单修改
        $form->update(request()->input());
        //表单字段修改
        $this->formFieldsUpdate($form);
        //表单控件字段修改
        $this->gridDataUpdate($form);
        return $form;
    }

    /**
     * @param $request
     * 表单字段数据修改
     */
    protected function formFieldsUpdate($form)
    {
        //字段的全部Id
        $fieldAllIds = $form->formFields->pluck('id')->all();
        //编辑的ID
        $editIds = [];
        foreach (request()->input('fields') as $k => $v) {
            $v['sort'] = $k;
            if (isset($v['id']) && intval($v['id'])) {
                //编辑
                $editIds[] = $v['id'];
                $field = Field::find($v['id']);
                $field->update($v);
                $field->validator()->sync(array_get($v, 'validator_id'));

            } else {
                //新增
                $v['form_id'] = $form->id;
                $field = Field::create($v);
                $field->validator()->sync(array_get($v, 'validator_id'));
            }
            //部门、员工、店铺控件（表数据删除）
            if ($field->widgets->count() > 0) {
                $field->widgets()->delete();
            }
            //员工、部门、店铺ID数据控件数据新增
            if (array_has($v, 'available_options') && $v['available_options']) {
                data_fill($v['available_options'], '*.field_id', $field->id);
                $field->widgets()->createMany($v['available_options']);
            }
        }

        //删除多余的字段
        $deleteId = array_diff($fieldAllIds, $editIds);
        $form->formFields->map(function ($field) use ($deleteId) {
            if (in_array($field->id, $deleteId)) {
                if ($field->widgets->count() > 0)
                    $field->widgets()->delete();
                $field->delete();
            }
        });

        $formDataTable = new FormDataTableService($form->id);
        ///删除表单字段控件表
        $form->formFields->each(function ($field) use ($formDataTable) {
            $formDataTable->destroyFormDataFieldTypeTable($field->key);
        });

        //表单字段data表与字段控件表  修改
        $formDataTable->updateFormDataTable();
    }

    /**
     * 修改列表控件数据
     * @param $form
     */
    protected function gridDataUpdate($form)
    {
        if (request()->has('grids') && request()->input('grids')) {
            //request 有控件
            $this->updateGrids($form);
        } else {
            //request 无控件
            $this->deleteGrids($form);
        }
    }

    /**
     * @param $form
     * 列表控件修改
     */
    protected function updateGrids($form)
    {
        //删除列表控件表
        if ($form->grids->count() > 0)
            $this->deleteFormGridsTable($form->grids);

        //控件全部ID
        $gridAllIds = $form->grids->pluck('id')->all();
        //控件编辑ID
        $gridEditIds = [];
        foreach (request()->input('grids') as $k => $grid) {
            if (array_has($grid, 'id') && $grid['id']) {
                //编辑
                $gridEditIds[] = $grid['id'];
                $formGrid = FormGrid::find($grid['id']);
                //表单控件数据修改
                $formGrid->update($grid);
                //控件字段修改
                $this->formGridFieldsUpdate($grid['fields'], $formGrid);
            } else {
                //新增
                $grid['form_id'] = $form->id;
                $this->gridItemSave($grid);
            }
        }
        //删除多余控件
        $deleteGridId = array_diff($gridAllIds, $gridEditIds);
        if ($deleteGridId) {
            $form->grids->map(function ($grid) use ($deleteGridId) {
                if (in_array($grid->id, $deleteGridId)) {
                    $grid->fields->map(function ($field) {
                        if ($field->widgets->count() > 0)
                            $field->widgets()->delete();
                        $field->validator()->sync([]);
                        $field->delete();
                    });
                    $grid->delete();
                }
            });
        }
    }

    /**
     * 表单控件字段修改
     * @param array $fields
     * @param $formGrid
     */
    protected function formGridFieldsUpdate(array $fields, $formGrid)
    {
        //字段全部data
        $data = [];
        //编辑字段ID
        $editId = [];
        //全部字段ID
        $allId = $formGrid->fields->pluck('id')->all();
        foreach ($fields as $k => $field) {
            $field['sort'] = $k;
            if (array_has($field, 'id') && $field['id']) {
                //编辑
                $editId[] = $field['id'];
                $fieldData = Field::find($field['id']);
                $fieldData->update($field);
                $fieldData->validator()->sync($field['validator_id']);
                //部门、员工、店铺控件
                if ($fieldData->widgets->count() > 0) {
                    $fieldData->widgets()->delete();
                }
                if (array_has($field, 'available_options') && $field['available_options']) {
                    data_fill($field['available_options'], '*.field_id', $fieldData->id);
                    $fieldData->widgets()->createMany($field['available_options']);
                }
                $fieldData = $fieldData->toArray();
            } else {
                //新增
                $field['form_id'] =$formGrid->form_id;
                $field['form_grid_id'] =$formGrid->id;
                $fieldData = $this->fieldsItemSave($field);//新增控件字段
            }
            $data[] = $fieldData;
        }

        $formDataTable = new FormDataTableService($formGrid->form_id);
        $gridData['key'] = $formGrid->key;
        $gridData['fields'] = $data;
        $formDataTable->createFormGridTable($gridData);//创建列表控件表

        //删除多余字段
        $deleteId = array_diff($allId, $editId);
        $formGrid->fields->map(function ($field) use ($deleteId) {
            if (in_array($field->id, $deleteId)) {
                if ($field->widgets->count() > 0)
                    $field->widgets()->delete();
                $field->validator()->sync([]);
                $field->delete();
            }
        });
    }

    /**
     * 删除列表控件相关数据
     * @param $formId
     */
    protected function deleteGrids($form)
    {
        if ($form->grids->count() > 0) {
            //删除控件与字段
            $this->deleteFormGridsFields($form);
            $this->deleteFormGridsTable($form->grids);//删除列表控件表
        }
    }

    /**
     * 删除控件与字段
     * @param array $formGridId
     * @param $formId
     */
    protected function deleteFormGridsFields($form)
    {
        $form->grids->map(function ($grid) {
            $grid->fields->each(function ($field) {
                $field->validator()->sync([]);
                $field->widgets()->delete();
            });
            $grid->fields()->delete();
        });
        $form->grids()->delete();
    }

    /**
     * 删除列表控件表
     * @param $formId
     */
    protected function deleteFormGridsTable($grids)
    {
        $grids->map(function ($grid) {
            $formDataTable = new FormDataTableService($grid->form_id);
            $formDataTable->destroyFormGridTable($grid->key);
            $grid->fields->each(function ($field) use ($formDataTable) {
                $formDataTable->destroyFormDataFieldTypeTable($field->key);
            });
        });
    }
    /*-------------------------------表单data无数据修改end----------------------------------------------*/


    /*------------------------修改流程的步骤表的隐藏、可写、必填字段start------------------*/
    /**
     *
     * 修改步骤表的字段key
     * @param $request
     */
    protected function updateStepFieldsKey($request)
    {
        $fields = $this->getFieldsKey($request->id);//表单字段与列表控件字段
        $flowStepsFieldsData = $this->getStepFields($request->id);//流程步骤字段数据
        if ($flowStepsFieldsData)
            $this->updateStepFields($flowStepsFieldsData, $fields);
    }

    /**
     * 修改步骤的字段
     * @param $data
     * @param $fields
     */
    protected function updateStepFields($data, array $fields)
    {
        $data->map(function ($flow) use ($fields) {
            $flow->steps->map(function ($step) use ($fields) {
                //可用字段处理
                if ($step->available_fields) {
                    $newField = $this->checkFields($step->available_fields, $fields);
                    $step->available_fields = $newField;
                    $step->save();
                }
                //隐藏字段处理
                if ($step->hidden_fields) {
                    $newField = $this->checkFields($step->hidden_fields, $fields);
                    $step->hidden_fields = $newField;
                    $step->save();
                }
                //编辑字段处理
                if ($step->editable_fields) {
                    $newField = $this->checkFields($step->editable_fields, $fields);
                    $step->editable_fields = $newField;
                    $step->save();
                }
                //必填字段处理
                if ($step->required_fields) {
                    $newField = $this->checkFields($step->required_fields, $fields);
                    $step->required_fields = $newField;
                    $step->save();
                }
            });
        });
    }

    /**
     * 获取新的字段
     * @param $fieldData
     * @param $allFields
     * @return array
     */
    protected function checkFields($fieldData, $allFields)
    {
        $newField = [];
        foreach ($fieldData as $v) {
            if (in_array($v, $allFields)) {
                $newField[] = $v;
            }
        }
        return $newField;
    }

    /**
     * 获取表单字段与列表控件字段
     * @param $formId
     */
    protected function getFieldsKey($formId)
    {
        $formDataTable = new FormDataTableService($formId);
        $formFieldsKeys = $formDataTable->getFormFields()->pluck('key')->all();
        //获取控件字段
        $gridData = FormGrid::with('fields')->where('form_id', $formId)->get();
        if ($gridData) {
            foreach ($gridData as $v) {
                //添加控件的key进去
                $formFieldsKeys[] = $v['key'];
                foreach ($v->fields as $item) {
                    $formFieldsKeys[] = $v['key'] . '.*.' . $item['key'];
                }
            }
        }
        return $formFieldsKeys;
    }

    /**
     * 获取步骤的字段
     * @param $formId
     */
    protected function getStepFields($formId)
    {
        $data = Flow::with(['steps' => function ($query) {
            $query->whereNull('deleted_at');
        }])
            ->where(['form_id' => $formId])
            ->whereNull('deleted_at')
            ->select('id', 'form_id')
            ->get();
        return $data;
    }

    /*------------------------修改流程的步骤表的隐藏、可写、必填字段start------------------*/
}