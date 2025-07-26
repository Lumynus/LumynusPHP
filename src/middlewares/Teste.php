<?php

namespace App\Middlewares;



class Teste 
{

    public function handle($teste,$v)
    {

        var_dump($v);
        
        return true;
     
    }
}
