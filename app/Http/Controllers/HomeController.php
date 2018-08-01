<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    
    public function __construct()
    {
        if (!empty($_GET['token']) && $_GET['token'] === '666') {
            
        } else {
            echo '404';exit;
        }
    }

    public function field()
    {
        $data = $this->getMdContent();

        return $data;
    }

    public function initTablesData()
    {
        $tables = \DB::select('SHOW TABLE STATUS ');
        foreach ($tables as $key => $table) {
            $columns = \DB::select("SHOW FULL FIELDS FROM `".$table->Name."`");
            $table->columns = $columns;
            $tables[$key] = $table;
        }
        return $tables;
    }

    private function getMdContent()
    {
        $tables = $this->initTablesData();
        $content = "数据字典\n";
        $content .= "\n";
        foreach ($tables as $key => $table){
            $content .= $this->tableFields($key, $table);
        }

        return $content;
    }


    private function tableFields($key, $table)
    {
        $content = "\n\n-------------------\n\n";
        $content .= "{$table->Name}\n\n";
        
        $content .= "| 字段 | 类型 | 备注 |\n|:---:|:---:|:---:|\n";
        foreach ($table->columns as $column){
            if (!in_array($column->Field, ['id','created_at','updated_at', 'delete_at'])) {
            $content .= "| {$column->Field} | {$column->Type} | {$column->Comment}|\n";
            }
        }
        return "<pre>{$content}</pre>";
    }
}