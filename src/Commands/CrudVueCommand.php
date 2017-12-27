<?php

namespace Appzcoder\CrudGenerator\Commands;

use File;
use Illuminate\Console\Command;

class CrudVueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:vue
                            {name : The name of the Crud.}
                            {--fields= : The field names for the form.}
                            {--vue-path= : The name of the vue path.}
                            {--route-group= : Prefix of the route group.}
                            {--pk=id : The name of the primary key.}
                            {--validations= : Validation rules for the fields.}
                            {--form-helper=html : Helper for the form.}
                            {--custom-data= : Some additional values to use in the crud.}
                            {--localize=no : Localize the vue? yes|no.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create vues for the Crud.';

    /**
     * vue Directory Path.
     *
     * @var string
     */
    protected $vueDirectoryPath;

    /**
     *  Form field types collection.
     *
     * @var array
     */
    protected $typeLookup = [
        'string' => 'text',
        'char' => 'text',
        'varchar' => 'text',
        'text' => 'textarea',
        'mediumtext' => 'textarea',
        'longtext' => 'textarea',
        'json' => 'textarea',
        'jsonb' => 'textarea',
        'binary' => 'textarea',
        'password' => 'password',
        'email' => 'email',
        'number' => 'number',
        'integer' => 'number',
        'bigint' => 'number',
        'mediumint' => 'number',
        'tinyint' => 'number',
        'smallint' => 'number',
        'decimal' => 'number',
        'double' => 'number',
        'float' => 'number',
        'date' => 'date',
        'datetime' => 'datetime-local',
        'timestamp' => 'datetime-local',
        'time' => 'time',
        'boolean' => 'radio',
        'enum' => 'select',
        'select' => 'select',
        'file' => 'file',
    ];

    /**
     * Variables that can be used in stubs
     *
     * @var array
     */
    protected $vars = [
        'formFields',
        'formFieldsHtml',
        'modelFieldsDefaultHtml',
        'postFieldsHtml',
        'resetFieldsJavaScript',
        'table',
        'tableRowHtml',
        'tableColumnsHtml',
        'varName',
        'crudName',
        'resetHtml',
        'crudNameCap',
        'crudNameSingular',
        'primaryKey',
        'modelName',
        'modelNameCap',
        'vueName',
        'routePrefix',
        'routePrefixCap',
        'routeGroup',
        'formHeadingHtml',
        'formBodyHtml',
        'vueTemplateDir',
        'formBodyHtmlForShowvue',
    ];

    /**
     * Form's fields.
     *
     * @var array
     */
    protected $formFields = [];

    /**
     * Html of Form's fields.
     *
     * @var string
     */
    protected $formFieldsHtml = '';

    /**
     * Number of columns to show from the table. Others are hidden.
     *
     * @var integer
     */
    protected $defaultColumnsToShow = 3;

    /**
     * Variable name with first letter in lowercase
     *
     * @var string
     */
    protected $varName = '';

    /**
     * Name of the Crud.
     *
     * @var string
     */
    protected $crudName = '';

    /**
     * Crud Name in capital form.
     *
     * @var string
     */
    protected $crudNameCap = '';

    /**
     * Crud Name in singular form.
     *
     * @var string
     */
    protected $crudNameSingular = '';

    /**
     * Primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Name of the Model.
     *
     * @var string
     */
    protected $modelName = '';
    
    /**
     * Reset html
     * @var string
     **/
    protected $resetHtml = '';

    /**
     * Name of the Model with first letter in capital
     *
     * @var string
     */
    protected $modelNameCap = '';
    
    /**
     * Default model fields in js templates
     * @var string
     **/
    protected $modelFieldsDefaultHtml = '';
    /**
     * Name of the vue Dir.
     *
     * @var string
     */
    protected $vueName = '';
    
    /**
     * Template for posting fields in ajax
     * @var string
     **/
    protected $postFieldsHtml = '';
    
    /**
     * Prefix of the route
     *
     * @var string
     */
    protected $routePrefix = '';

    /**
     * Prefix of the route with first letter in capital letter
     *
     * @var string
     */
    protected $routePrefixCap = '';

    /**
     * Name or prefix of the Route Group.
     *
     * @var string
     */
    protected $routeGroup = '';

    /**
     * Html of the form heading.
     *
     * @var string
     */
    protected $formHeadingHtml = '';

    /**
     * Html of the form body.
     *
     * @var string
     */
    protected $formBodyHtml = '';

    /**
     * Table columns html
     * @var string
     **/
    protected $tableColumnsHtml = '';
    
    /**
     * Table row html
     * @var string
     **/
    protected $tableRowHtml = '';

    /**
     * Html of vue to show.
     *
     * @var string
     */
    protected $formBodyHtmlForShowvue = '';

    /**
     * User defined values
     *
     * @var array
     */
    protected $customData = [];

    /**
     * Template directory where vues are generated
     *
     * @var string
     */
    protected $vueTemplateDir = '';

    /**
     * Delimiter used for replacing values
     *
     * @var array
     */
    protected $delimiter;

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();

        if (config('crudgenerator.vue_columns_number')) {
            $this->defaultColumnsToShow = config('crudgenerator.vue_columns_number');
        }

        $this->delimiter = config('crudgenerator.custom_delimiter')
            ? config('crudgenerator.custom_delimiter')
            : ['%%', '%%'];
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $formHelper = $this->option('form-helper');
        $this->vueDirectoryPath = config('crudgenerator.custom_template')
            ? config('crudgenerator.path') . 'views/' . $formHelper . '/'
            : __DIR__ . '/../stubs/views/' . $formHelper . '/';
       

        $this->crudName = strtolower($this->argument('name'));
        $this->varName = lcfirst($this->argument('name'));
        $this->crudNameCap = ucwords($this->crudName);
        $this->crudNameSingular = str_singular($this->crudName);
        $this->modelName = str_singular($this->argument('name'));
        $this->modelNameCap = ucfirst($this->modelName);
        $this->customData = $this->option('custom-data');
        $this->primaryKey = $this->option('pk');
        $this->routeGroup = ($this->option('route-group'))
            ? $this->option('route-group') . '/'
            : $this->option('route-group');
        $this->routePrefix = ($this->option('route-group')) ? $this->option('route-group') : '';
        $this->routePrefixCap = ucfirst($this->routePrefix);
        $this->vueName = snake_case($this->argument('name'), '-');

        $vueDirectory = config('vue.paths')[0] . '/';
        if ($this->option('vue-path')) {
            $this->uservuePath = $this->option('vue-path');
            $path = $vueDirectory . $this->uservuePath;
        } else {
            $path = $vueDirectory . $this->vueName . '/';
        }

        $this->vueTemplateDir = isset($this->uservuePath)
            ? $this->uservuePath . '.' . $this->vueName
            : $this->vueName;
 
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $fields = $this->option('fields');
        $fieldsArray = explode(';', $fields);

        $this->formFields = [];
        $this->modelFieldsDefaultArray = [];
        $this->postFieldsArray = [];

        $validations = $this->option('validations');

        if ($fields) {
            $x = 0;
            foreach ($fieldsArray as $item) {
                $itemArray = explode('#', $item);
                $this->modelFieldsDefaultArray[] = $this->createDefaultField($itemArray);
                $this->postFieldsArray[] = $this->modelName . ': this.' . $this->modelName . '.' . $itemArray[0];
                $this->tableColumnsHtml .= '<th>' . trim($itemArray[0]) . "</th>";
                $this->tableRowHtml .= "<td>{{" . $this->modelName . "." . trim($itemArray[0]) . "}}</td>";
                $this->resetHtml .= 'this.' . $this->modelName . '.' . trim($itemArray[0]) . ' = \'\';';

                $this->formFields[$x]['name'] = trim($itemArray[0]);
                $this->formFields[$x]['type'] = trim($itemArray[1]);
                $this->formFields[$x]['required'] = preg_match('/' . $itemArray[0] . '/', $validations) ? true : false;


                $x++;
            }
        }
        $this->modelFieldsDefaultHtml = implode(",", $this->modelFieldsDefaultArray);
        $this->postFieldsHtml = implode(",", $this->postFieldsArray);

        foreach ($this->formFields as $item) {
            $this->formFieldsHtml .= $this->createField($item);
        }

        $i = 0;
        foreach ($this->formFields as $key => $value) {
            if ($i == $this->defaultColumnsToShow) {
                break;
            }

            $field = $value['name'];
            $label = ucwords(str_replace('_', ' ', $field));
            if ($this->option('localize') == 'yes') {
                $label = '{{ trans(\'' . $this->crudName . '.' . $field . '\') }}';
            }
            $this->formHeadingHtml .= '<th>' . $label . '</th>';
            $this->formBodyHtml .= '<td>{{ $item->' . $field . ' }}</td>';
            $this->formBodyHtmlForShowvue .= '<tr><th> ' . $label . ' </th><td> {{ $%%crudNameSingular%%->' . $field . ' }} </td></tr>';

            $i++;
        }

        $this->templateStubs($path);

        $this->info('vue created successfully.');
    }
    private function createDefaultField($itemArray) {
        $str = trim($itemArray[0]) . ': ';
        if ($itemArray[1] == 'object') {
            $str .= '{}';
        } else {
            $str .= "''";
        }
        return $str;
    }
    /**
     * Default template configuration if not provided
     *
     * @return array
     */
    private function defaultTemplating()
    {
        return [
            'index' => ['formHeadingHtml', 'resetHtml', 'modelFieldsDefaultHtml', 'postFieldsHtml', 'formBodyHtml', 'crudName', 'crudNameCap', 'tableRowHtml', 'tableColumnsHtml', 'modelNameCap', 'modelName', 'vueName', 'routeGroup', 'primaryKey'],
        ];
    }

    /**
     * Generate files from stub
     *
     * @param $path
     */
    protected function templateStubs($path)
    {
        $dynamicvueTemplate = config('crudgenerator.dynamic_vue_template')
            ? config('crudgenerator.dynamic_vue_template')
            : $this->defaultTemplating();
    
        foreach ($dynamicvueTemplate as $name => $vars) {
            if ($name == 'index') {
                $file = $this->vueDirectoryPath . $name . '.vue.stub';
                $newFile = $path . DIRECTORY_SEPARATOR . $this->modelNameCap . '.vue';
                if (!File::copy($file, $newFile)) {
                    echo "failed to copy $file...\n";
                } else {
                    $this->templateVars($newFile, $vars);
                    $this->userDefinedVars($newFile);
                }
            }
        }
    }

    /**
     * Update specified values between delimiter with real values
     *
     * @param $file
     * @param $vars
     */
    protected function templateVars($file, $vars)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        foreach ($vars as $var) {
            $replace = $start . $var . $end;
            if (in_array($var, $this->vars)) {
                File::put($file, str_replace($replace, $this->$var, File::get($file)));
            }
        }
    }

    /**
     * Update custom values between delimiter with real values
     *
     * @param $file
     */
    protected function userDefinedVars($file)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        if ($this->customData !== null) {
            $customVars = explode(';', $this->customData);
            foreach ($customVars as $rawVar) {
                $arrayVar = explode('=', $rawVar);
                File::put($file, str_replace($start . $arrayVar[0] . $end, $arrayVar[1], File::get($file)));
            }
        }
    }

    /**
     * Form field wrapper.
     *
     * @param  string $item
     * @param  string $field
     *
     * @return string
     */
    protected function wrapField($item, $field)
    {
        $formGroup = File::get($this->vueDirectoryPath . 'form-fields/wrap-field.vue.stub');

        $labelText = "'" . ucwords(strtolower(str_replace('_', ' ', $item['name']))) . "'";

        if ($this->option('localize') == 'yes') {
            $labelText = 'trans(\'' . $this->crudName . '.' . $item['name'] . '\')';
        }

        return sprintf($formGroup, $item['name'], $labelText, $field);
    }

    /**
     * Form field generator.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createField($item)
    {
        switch ($this->typeLookup[$item['type']]) {
            case 'password':
                return $this->createPasswordField($item);
            case 'datetime-local':
            case 'time':
                return $this->createInputField($item);
            case 'radio':
                return $this->createRadioField($item);
            case 'textarea':
                return $this->createTextareaField($item);
            case 'select':
            case 'enum':
                return $this->createSelectField($item);
            default: // text
                return $this->createFormField($item);
        }
    }

    /**
     * Create a specific field using the form helper.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createFormField($item)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $required = $item['required'] ? 'required' : '';

        $markup = File::get($this->vueDirectoryPath . 'form-fields/form-field.vue.stub');
        $markup = str_replace($start . 'required' . $end, $required, $markup);
        $markup = str_replace($start . 'fieldType' . $end, $this->typeLookup[$item['type']], $markup);
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField(
            $item,
            $markup
        );
    }

    /**
     * Create a password field using the form helper.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createPasswordField($item)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $required = $item['required'] ? 'required' : '';

        $markup = File::get($this->vueDirectoryPath . 'form-fields/password-field.vue.stub');
        $markup = str_replace($start . 'required' . $end, $required, $markup);
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField(
            $item,
            $markup
        );
    }

    /**
     * Create a generic input field using the form helper.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createInputField($item)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $required = $item['required'] ? 'required' : '';

        $markup = File::get($this->vueDirectoryPath . 'form-fields/input-field.vue.stub');
        $markup = str_replace($start . 'required' . $end, $required, $markup);
        $markup = str_replace($start . 'fieldType' . $end, $this->typeLookup[$item['type']], $markup);
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField(
            $item,
            $markup
        );
    }

    /**
     * Create a yes/no radio button group using the form helper.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createRadioField($item)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $markup = File::get($this->vueDirectoryPath . 'form-fields/radio-field.vue.stub');
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField($item, sprintf($markup, $item['name']));
    }

    /**
     * Create a textarea field using the form helper.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createTextareaField($item)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $required = $item['required'] ? 'required' : '';

        $markup = File::get($this->vueDirectoryPath . 'form-fields/textarea-field.vue.stub');
        $markup = str_replace($start . 'required' . $end, $required, $markup);
        $markup = str_replace($start . 'fieldType' . $end, $this->typeLookup[$item['type']], $markup);
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField(
            $item,
            $markup
        );
    }

    /**
     * Create a select field using the form helper.
     *
     * @param  array $item
     *
     * @return string
     */
    protected function createSelectField($item)
    {
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $required = $item['required'] ? 'required' : '';

        $markup = File::get($this->vueDirectoryPath . 'form-fields/select-field.vue.stub');
        $markup = str_replace($start . 'required' . $end, $required, $markup);
        $markup = str_replace($start . 'options' . $end, $item['options'], $markup);
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField(
            $item,
            $markup
        );
    }
}
