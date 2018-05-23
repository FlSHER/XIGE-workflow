<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/14/014
 * Time: 17:17
 */

namespace App\Repository;


use App\Models\Field;
use App\Models\FlowRun;
use App\Models\FormGrid;
use Illuminate\Support\Facades\DB;

class FormRepository
{
    /**
     * 获取全部字段（包含控件）
     * @param $formId
     * @return array
     */
    public function getFields($formId)
    {
        $formFields = Field::where('form_id', $formId)->whereNull('form_grid_id')->orderBy('sort', 'asc')->get();
        $gridFields = $this->getGridData($formId);
        $allFields = ['form' => $formFields, 'grid' => $gridFields];
        return collect($allFields);
    }

    /**
     * 获取表单data数据与控件数据
     * @param $flowRun
     */
    public function getFormData($flowRun = null)
    {
        if ($flowRun == null) {//第一步骤无表单data数据
            $formData = [];
        } else {
            if (is_numeric($flowRun)) {
                $flowRun = FlowRun::find($flowRun);
            }
            $gridKeys = $this->getGridData($flowRun->form_id)->pluck('key');
            $formData = $this->getFormFieldsData($flowRun, $gridKeys);
        }
        return (array)$formData;
    }

    /**
     * 获取去除hidden的字段
     * @param $hiddenFields
     * @param $formId
     */
    public function getExceptHiddenFields($hiddenFields, $formId)
    {
        $allFields = $this->getFields($formId);//获取全部字段
        $fields = $this->exceptHiddenFields($allFields, $hiddenFields);//去除了隐藏的字段
        return $fields;
    }


    /**
     * 获取editable的字段信息
     * @param $editableFields
     * @param $formId
     */
    public function getOnlyEditableFields($editableFields, $formId)
    {
        $allFields = $this->getFields($formId);//获取全部字段
        $fields = $this->onlyEditableFields($allFields, $editableFields);//包含可写的字段
        return $fields;
    }


    /**
     * 替换表单的formData数据
     * @param $requestFormData
     * @param $databaseFormData
     */
    public function replaceFormData($requestFormData, $databaseFormData)
    {
        foreach ($databaseFormData as $k => $v) {
            if (array_has($requestFormData, $k)) {
                if (is_array($v)) {
                    if (count($v) > 0) {
                        $databaseFormData[$k] = $this->replaceGridFormData($v, $requestFormData);
                    }
                }
                $databaseFormData[$k] = $requestFormData[$k];
            }
        }
        return $databaseFormData;
    }

    /**
     * 替换控件数据
     * @param $gridData
     * @param $requestFormData
     * @return mixed
     */
    protected function replaceGridFormData($gridData, $requestFormData)
    {
        foreach ($gridData as $gridKey => $gridItem) {
            foreach ($gridItem as $field => $value) {
                if (array_has($requestFormData[$gridKey], $field)) {
                    $gridData[$gridKey][$field] = $requestFormData[$gridKey][$field];
                }
            }
        }
        return $gridData;
    }

    /**
     * 获取表单控件数据与控件字段
     * @param $formId
     */
    public function getGridData($formId)
    {
        $gridData = FormGrid::with(['fields' => function ($query) {
            $query->orderBy('sort', 'asc');
        }])->whereFormId($formId)->get();
        return $gridData;
    }

    /**
     * 获取表单data数据
     * @param $flowRun
     * @return mixed
     */
    protected function getFormFieldsData($flowRun, $gridKeys)
    {
        $tableName = 'form_data_' . $flowRun->form_id;
        $runId = $flowRun->id;
        $formData = (array)DB::table($tableName)->whereRunId($runId)->first();
        if (!empty($gridKeys)) {
            foreach ($gridKeys as $key) {
                $formData[$key] = DB::table($tableName . '_' . $key)->where('data_id', $formData['id'])
                    ->get()->map(function ($item) {
                        return (array)$item;
                    })->toArray();
            }
        }
        return $formData;
    }

    /**
     * 去除hidden字段
     * @param $allFields
     * @param $hiddenFields
     */
    protected function exceptHiddenFields($allFields, $hiddenFields)
    {
        //去除表单的hidden字段
        $allFields['form'] = $allFields['form']->filter(function ($field) use ($hiddenFields) {
            return !in_array($field->key, $hiddenFields);
        })->pluck([]);

        //去除控件的hidden字段
        $allFields['grid'] = $allFields['grid']->map(function ($grid) use ($hiddenFields) {
            $gridKey = $grid->key;
            $fields = $grid->fields->filter(function ($field) use ($gridKey, $hiddenFields) {
                $key = $gridKey . '.*.' . $field->key;
                return !in_array($key, $hiddenFields);
            })->pluck([]);
            $gridData = $grid->toArray();
            $gridData['fields'] = $fields;
            return collect($gridData);
        });
        return $allFields;
    }

    /**
     * 获取包含可写的字段信息
     * @param $allFields
     * @param $editableFields
     * @return mixed
     */
    protected function onlyEditableFields($allFields, $editableFields)
    {
        $allFields['form'] = $allFields['form']->filter(function ($field) use ($editableFields) {
            return in_array($field->key, $editableFields);
        })->pluck([]);
        $allFields['grid'] = $allFields['grid']->map(function ($grid) use ($editableFields) {
            $gridKey = $grid->key;
            $fields = $grid->fields->filter(function ($field) use ($gridKey, $editableFields) {
                $key = $gridKey . '.*.' . $field->key;
                return in_array($key, $editableFields);
            })->pluck([]);
            $gridData = $grid->toArray();
            $gridData['fields'] = $fields;
            return collect($gridData);
        });
        return $allFields;
    }
}