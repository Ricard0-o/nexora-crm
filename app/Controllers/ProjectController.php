<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\View;
use App\Repositories\ProjectRepository;
use App\Services\ProjectService;
use RuntimeException;

final class ProjectController
{
    public function index(Request $request): void { View::render('projects/index', ['projects' => (new ProjectRepository())->all()]); }
    public function create(Request $request): void
    {
        try { View::render('projects/form', array_merge((new ProjectService())->formData(), ['mode'=>'create'])); unset($_SESSION['_validation']); }
        catch (RuntimeException $e) { $_SESSION['_flash']['error']=$e->getMessage(); Response::redirect('/projects'); }
    }
    public function store(Request $request): void
    {
        $_SESSION['_old']=$request->all();
        try { $id=(new ProjectService())->create($request->all()); unset($_SESSION['_old'],$_SESSION['_validation']); $_SESSION['_flash']['success']="Projeto #{$id} criado com sucesso."; Response::redirect('/projects'); }
        catch (RuntimeException $e) { $_SESSION['_flash']['error']=$e->getMessage(); Response::redirect('/projects/create'); }
    }
    public function edit(Request $request): void
    {
        $id=(int)$request->input('id',0);
        try { View::render('projects/form', array_merge((new ProjectService())->formData($id), ['mode'=>'edit'])); unset($_SESSION['_validation']); }
        catch (RuntimeException $e) { $_SESSION['_flash']['error']=$e->getMessage(); Response::redirect('/projects'); }
    }
    public function update(Request $request): void
    {
        $id=(int)$request->input('id',0); $_SESSION['_old']=$request->all();
        try { (new ProjectService())->update($id,$request->all()); unset($_SESSION['_old'],$_SESSION['_validation']); $_SESSION['_flash']['success']='Projeto atualizado com sucesso.'; Response::redirect('/projects'); }
        catch (RuntimeException $e) { $_SESSION['_flash']['error']=$e->getMessage(); Response::redirect('/projects/edit?id='.$id); }
    }
}
