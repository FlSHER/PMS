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

    'accepted'             => ':attribute 必须接受',
    'active_url'           => ':attribute 不是有效的URL',
    'after'                => ':attribute 以后一定要约会吗? :date',
    'after_or_equal'       => ':attribute 必须是约会之后还是等于 :date',
    'alpha'                => ':attribute 可能只包含字母',
    'alpha_dash'           => ':attribute 可能只包含字母、数字和破折号',
    'alpha_num'            => ':attribute 可能只包含字母和数字',
    'array'                => ':attribute 必须是一个数组',
    'before'               => ':attribute 以前一定是约会吗 :date',
    'before_or_equal'      => ':attribute 必须是集会之前还是等于 :date',
    'between'              => [
        'numeric' => ':attribute 之间必须 :min 和 :max',
        'file'    => ':attribute 之间必须 :min 和 :max 字节',
        'string'  => ':attribute 之间必须 :min 和 :max 字符',
        'array'   => ':attribute 之间必须有 :min 和 :max 项',
    ],
    'boolean'              => ':attribute 字段必须为真或假',
    'confirmed'            => ':attribute 确认不匹配',
    'date'                 => ':attribute 不是一个有效日期',
    'date_format'          => ':attribute 格式不匹配吗 :format',
    'different'            => ':attribute 和 :other 必须是不同的',
    'digits'               => ':attribute 必须 :digits 位数',
    'digits_between'       => ':attribute 之间必须 :min 和 :max 数字',
    'dimensions'           => ':attribute 无效的图像尺寸',
    'distinct'             => ':attribute 字段具有重复的值',
    'email'                => ':attribute 必须是一个有效的电子邮件地址',
    'exists'               => '选择 :attribute 是无效的',
    'file'                 => ':attribute 必须是一个文件',
    'filled'               => ':attribute 字段必须有一个值',
    'image'                => ':attribute 必须是一个形象',
    'in'                   => '选择 :attribute 是无效的',
    'in_array'             => ':attribute 字段不存在 :other',
    'integer'              => ':attribute 必须是一个整数',
    'ip'                   => ':attribute 必须是有效的IP地址',
    'ipv4'                 => ':attribute 必须是一个有效的IPv4地址',
    'ipv6'                 => ':attribute 必须是有效的IPv6地址',
    'json'                 => ':attribute 必须是一个有效的JSON字符串',
    'max'                  => [
        'numeric' => ':attribute 可能不大于 :max',
        'file'    => ':attribute 可能不大于 :max 字节',
        'string'  => ':attribute 可能不大于 :max 字符',
        'array'   => ':attribute 可能不大于 :max 项',
    ],
    'mimes'                => ':attribute 必须是一个类型的文件: :values',
    'mimetypes'            => ':attribute 必须是一个类型的文件: :values',
    'min'                  => [
        'numeric' => ':attribute 必须至少 :min',
        'file'    => ':attribute 必须至少 :min 字节',
        'string'  => ':attribute 必须至少 :min 字符',
        'array'   => ':attribute 必须至少有 :min 项',
    ],
    'not_in'               => '选择 :attribute 是无效的',
    'numeric'              => ':attribute 必须是一个数字',
    'present'              => ':attribute 字段必须存在',
    'regex'                => ':attribute 格式是无效的',
    'required'             => ':attribute 字段是必需验证',
    'required_if'          => ':attribute 字段是必需的,当 :other 是 :value',
    'required_unless'      => ':attribute 字段是必需的,除非 :other 是在 :values',
    'required_with'        => ':attribute 字段是必需的,当 :values 存在',
    'required_with_all'    => ':attribute 字段是必需的,当 :values 存在',
    'required_without'     => ':attribute 字段是必需的,当 :values 不存在',
    'required_without_all' => ':attribute 不需要字段 :values 存在',
    'same'                 => ':attribute 和 :other 必须匹配',
    'size'                 => [
        'numeric' => ':attribute 必须 :size',
        'file'    => ':attribute 必须 :size 字节',
        'string'  => ':attribute 必须 :size 字符',
        'array'   => ':attribute 必须包含 :size 项目',
    ],
    'string'               => ':attribute 必须是一个字符串',
    'timezone'             => ':attribute 必须是有效区域',
    'unique'               => ':attribute 已经采取',
    'uploaded'             => ':attribute 上传失败',
    'url'                  => ':attribute 格式是无效的',

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
