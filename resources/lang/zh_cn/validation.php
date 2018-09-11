<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => ':attribute 必须为“是”。',
    'active_url'           => 'The :attribute is not a valid URL.',
    'after'                => ':attribute 必须晚于 :date。',
    'after_or_equal'       => ':attribute 必须晚于或等于 :date。',
    'alpha'                => ':attribute 只能包含字母。',
    'alpha_dash'           => ':attribute 只能包含字母、数字和点。',
    'alpha_num'            => ':attribute 只能包含字母和数字。',
    'array'                => ':attribute 必须是数组。',
    'before'               => ':attribute 必须早于 :date。',
    'before_or_equal'      => ':attribute 必须早于或等于 :date。',
    'between'              => [
        'numeric' => ':attribute 必须介于 :min 和 :max 之间。',
        'file'    => ':attribute 文件大小必须介于 :minK 和 :maxK 之间。',
        'string'  => ':attribute 必须包含 :min 和 :max 个字符。',
        'array'   => ':attribute 必须包含 :min 到 :max 个元素。',
    ],
    'boolean'              => ':attribute 必须为“是”或“否”。',
    'confirmed'            => ':attribute 两次输入不一致。',
    'date'                 => ':attribute 必须是日期格式。',
    'date_format'          => ':attribute 必须是格式为 :format 的日期。',
    'different'            => ':attribute 和 :other 不能相同。',
    'digits'               => 'The :attribute must be :digits digits.',
    'digits_between'       => ':attribute 之间必须 :min 和 :max 数字.',
    'dimensions'           => 'The :attribute has invalid image dimensions.',
    'distinct'             => 'The :attribute 字段具有重复的值.',
    'email'                => ':attribute 必须是电子邮件格式。',
    'exists'               => ':attribute 记录不存在。',
    'file'                 => ':attribute 必须的文件.',
    'filled'               => 'The :attribute field must have a value.',
    'image'                => ':attribute 必须是一个图片.',
    'in'                   => ':attribute 不存在。',
    'in_array'             => ':attribute 必须包含在 :other 中。',
    'integer'              => ':attribute 必须是整数。',
    'ip'                   => ':attribute 必须是一个有效的IP地址。',
    'ipv4'                 => ':attribute 必须是一个有效的IPv4地址。',
    'ipv6'                 => ':attribute 必须是一个有效的IPv6地址。',
    'json'                 => ':attribute 必须是一个有效的JSON字符串',
    'max'                  => [
        'numeric' => ':attribute 不能大于 :max 。',
        'file'    => ':attribute 文件大小不能大于 :maxK 。',
        'string'  => ':attribute 长度不能大于 :max 。',
        'array'   => ':attribute 不能包含超过 :max 个元素。',
    ],
    'mimes'                => ':attribute 必须是 :values 格式的文件。',
    'mimetypes'            => ':attribute 必须是 :values 格式的文件。',
    'min'                  => [
        'numeric' => ':attribute 不能小于 :min 。',
        'file'    => ':attribute 文件大小不能小于 :minK 。',
        'string'  => ':attribute 长度不能小于 :min 。',
        'array'   => ':attribute 必须包含 :min 个以上元素。',
    ],
    'not_in'               => '所选的 :attribute 是无效的.',
    'numeric'              => ':attribute 必须是数字。',
    'present'              => 'The :attribute field must be present.',
    'regex'                => ':attribute 格式不正确。',
    'required'             => ':attribute 不能为空。',
    'required_if'          => '当 :other 等于 :value 时，:attribute 不能为空。',
    'required_unless'      => 'The :attribute field is required unless :other is in :values.',
    'required_with'        => ':values 存在时， :attribute 不能为空。',
    'required_with_all'    => ':values 都存在时， :attribute 不能为空。',
    'required_without'     => ':values 不存在时， :attribute 不能为空。',
    'required_without_all' => ':values 都不存在时， :attribute 不能为空。',
    'same'                 => ':attribute 和 :other 必须相同。',
    'size'                 => [
        'numeric' => 'The :attribute must be :size.',
        'file'    => 'The :attribute must be :size kilobytes.',
        'string'  => 'The :attribute must be :size characters.',
        'array'   => 'The :attribute must contain :size items.',
    ],
    'string'               => 'The :attribute 必须是一个字符串.',
    'timezone'             => 'The :attribute must be a valid zone.',
    'unique'               => ':attribute 已经存在。',
    'uploaded'             => ':attribute 上传失败.',
    'url'                  => 'The :attribute 格式是无效的.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

];
