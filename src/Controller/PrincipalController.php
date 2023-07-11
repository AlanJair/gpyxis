<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace App\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Pimcore\Controller\FrontendController;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Categoria;
use Pimcore\Model\DataObject\Category;
use App\Model\Product\Proyecto;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ElasticSearch\AbstractElasticSearch;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

class PrincipalController extends BaseController
{
    
    /**
     * @Route("/proyecto/{id}", name="proyecto_detalle")
     */
    public function projectAction(Request $request, $id)
    {
        $project = Proyecto::getById($id);
        
        return $this->render('gepyxis/content/project.html.twig', [ 'project' => $project ]);
    }

    // Enlistar proyectos
    public function listingObjectProject(){ return $projects = new DataObject\Proyecto\Listing(); }

    // Proyectos
    public function projectsAction(Request $request, PaginatorInterface $paginator)
    {
        $paramsPro = [];
        /* Una forma de llamar todos los dataObjects de la carpeta Servicios en este caso
        $projectObjectsFolder = Service::createFolderByPath('/GePyxis/Proyectos/Servicios/');
        $paramsPro['projects'] = projectObjectsFolder->getChildren(); */
        $entries = $this->listingObjectProject();
        $search = strip_tags($request->get('search'));
        $entries->setCondition("titulo LIKE '%$search%' OR nombre LIKE '%$search%'");
        /* $entries->setCondition('categorias IN (",1209,")'); */ // Descubrir como funciona con categorias
        $entries->load();
        
        $c= ""; // Para debuggear. Ej. enviar un "Hola Mundo"
       
        $paginator = $paginator->paginate(
            $entries,
            $request->get('page', 1),
            10
        );

        return $this->render('gepyxis/content/editProjects.html.twig', [
            'paginator' => $paginator,
            'paginationVariables' => $paginator->getPaginationData(),
            'term' => $search,
            'debug' => $c
        ]);

        
    }

    public function aboutAction()
    {
        return $this->render('gepyxis/content/about.html.twig');
    }

    // Soluciones
    public function solutionAction(Request $request, PaginatorInterface $paginator)
    {
        $paramsSer = [];

        $sectionCategories = Category::getByPath('/GePyxis/Categorias/proyectos/soluciones');
        $projectsId = array();

        $pathProjects = $sectionCategories->getProyectos();
        foreach ($pathProjects as $key => $value) {
            $project = Proyecto::getByPath($value);
            if (!in_array($project->getId(), $projectsId)) {
                array_push($projectsId, $project->getId());
            }
        }
        
        if($sectionCategories->getChildren()){
            $paramsSer['sectionCategories'] = $sectionCategories->getChildren();    

            foreach ($sectionCategories->getChildren() as $key => $value) {
                $pathProject = $value->getProyectos();
                foreach ($pathProject as $key => $value) {
                    $project = Proyecto::getByPath($value);
                    if (!in_array($project->getId(), $projectsId)) {
                        array_push($projectsId, $project->getId());
                    }
                }
            }
        }
        
        /* $entries = new Listing(Proyecto::class); */
        
        $category = Category::getById($request->get('categoryselect'));
        $search = strip_tags($request->get('searchsection'));

        if(!empty($projectsId)){
            $entries = $this->listingObjectProject();
            $keyTable = $entries->getClassId();

            $entries->setCondition('oo_id IN (' . implode(',', $projectsId) . ')');
            $query = "SELECT * FROM object_$keyTable WHERE oo_id IN (" . implode(',', $projectsId) . ")" ;

            switch ([$search, $category]) {
                case (!empty($search) && $category):
                    $entries->setCondition("categories LIKE '%" . ($category->getId()) . "%' AND (titulo LIKE '%$search%' OR nombre LIKE '%$search%') AND EXISTS (" . $query . ")");
                    /* $paramsSer["categoriaid"] = $category->getId(); */
                    break;
                case (!empty($search) && !($category)):
                    $entries->setCondition("oo_id IN (" . implode(',', $projectsId) . ") AND (titulo LIKE '%$search%' OR nombre LIKE '%$search%')");
                    break;
                case (empty($search) && $category):
                    $entries->setCondition('categories LIKE "%' . ($category->getId()) . '%" AND oo_id IN (' . implode(',', $projectsId) . ')');
                    /* $paramsSer["categoriaid"] = 'cats IN (",' . ($category->getId()) . ',")' */;
                    break;
            }
        } else{
            $entries = $this->listingObjectProject();
            $entries->setCondition('oo_id IN (0)');
        }
        $paramsSer["debug"] = $projectsId;
        /* $paramsSer["debug"] = $sectionCategories->getChildren(); */
        $entries->load();
        /* Otra forma forma de enviar opciones
        $options = '';
        foreach ($sectionCategories->getChildren() as $category) {
            $options .= sprintf('<option value="%d">%s</option>', $category->getId(), $category->getTitulo());
        }
        $paramsSer["options"] = $options; */

        $c= ""; // Para debuggear. Ej. enviar un "Hola Mundo"
        $paramsSer["term"] = $search;
        $paramsSer["category"] = $category;

        $paginator = $paginator->paginate(
            $entries,
            $request->get('page', 1),
            10
        );
        $paramsSer['results'] = $paginator;

        return $this->render('gepyxis/content/section.html.twig', $paramsSer);
    }

// Servicios
    public function servicesAction(Request $request, PaginatorInterface $paginator)
    {
        $paramsSer = [];

        $sectionCategories = Category::getByPath('/GePyxis/Categorias/proyectos/servicios');
        $projectsId = array();

        $pathProjects = $sectionCategories->getProyectos();
        foreach ($pathProjects as $key => $value) {
            $project = Proyecto::getByPath($value);
            if (!in_array($project->getId(), $projectsId)) {
                array_push($projectsId, $project->getId());
            }
        }
        
        if($sectionCategories->getChildren()){
            $paramsSer['sectionCategories'] = $sectionCategories->getChildren();    

            foreach ($sectionCategories->getChildren() as $key => $value) {
                $pathProject = $value->getProyectos();
                foreach ($pathProject as $key => $value) {
                    $project = Proyecto::getByPath($value);
                    if (!in_array($project->getId(), $projectsId)) {
                        array_push($projectsId, $project->getId());
                    }
                }
            }
        }
        
        /* $entries = new Listing(Proyecto::class); */
        
        $category = Category::getById($request->get('categoryselect'));
        $search = strip_tags($request->get('searchsection'));

        if(!empty($projectsId)){
            $entries = $this->listingObjectProject();
            $keyTable = $entries->getClassId();

            $entries->setCondition('oo_id IN (' . implode(',', $projectsId) . ')');
            $query = "SELECT * FROM object_$keyTable WHERE oo_id IN (" . implode(',', $projectsId) . ")" ;

            switch ([$search, $category]) {
                case (!empty($search) && $category):
                    $entries->setCondition("categories LIKE '%" . ($category->getId()) . "%' AND (titulo LIKE '%$search%' OR nombre LIKE '%$search%') AND EXISTS (" . $query . ")");
                    /* $paramsSer["categoriaid"] = $category->getId(); */
                    break;
                case (!empty($search) && !($category)):
                    $entries->setCondition("oo_id IN (" . implode(',', $projectsId) . ") AND (titulo LIKE '%$search%' OR nombre LIKE '%$search%')");
                    break;
                case (empty($search) && $category):
                    $entries->setCondition('categories LIKE "%' . ($category->getId()) . '%" AND oo_id IN (' . implode(',', $projectsId) . ')');
                    /* $paramsSer["categoriaid"] = 'cats IN (",' . ($category->getId()) . ',")' */;
                    break;
            }
        } else{
            $entries = $this->listingObjectProject();
            $entries->setCondition('oo_id IN (0)');
        }
        $paramsSer["debug"] = $projectsId;
        /* $paramsSer["debug"] = $sectionCategories->getChildren(); */
        $entries->load();
        /* Otra forma forma de enviar opciones
        $options = '';
        foreach ($sectionCategories->getChildren() as $category) {
            $options .= sprintf('<option value="%d">%s</option>', $category->getId(), $category->getTitulo());
        }
        $paramsSer["options"] = $options; */

        $c= ""; // Para debuggear. Ej. enviar un "Hola Mundo"
        $paramsSer["term"] = $search;
        $paramsSer["category"] = $category;

        $paginator = $paginator->paginate(
            $entries,
            $request->get('page', 1),
            10
        );
        $paramsSer['results'] = $paginator;

        return $this->render('gepyxis/content/section.html.twig', $paramsSer);
    }
}
