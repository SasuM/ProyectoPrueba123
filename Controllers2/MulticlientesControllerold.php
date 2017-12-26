<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\Recurso;

class MulticlientesController extends AbstractActionController
{
	/**
	 * Entity manager.
	 * @var Doctrine\ORM\EntityManager
	 */
	private $entityManager;
	
	public function __construct($entityManager)
	{
		$this->entityManager = $entityManager;
	}
	
	public function indexAction(){
		$recurso = $this->entityManager->getRepository(Recurso::class)
		->findBy(array('idRecursoPadre'=>'106'));
		if(is_array($recurso)){
			$recursos = $recurso;
		}
		else
			$recursos[0] = $recurso;
			return new ViewModel([
					'recursos' => $recurso,
			]);
			$response = $this->getResponse();
				
			return $response;
		
	}
	
	
	public function TabsHijosAction(){
		$idpadre = $this->getRequest()->getPost("Padre");
		$recurso = $this->entityManager->getRepository(Recurso::class)
		->findBy(array('idRecursoPadre'=>'107'), array('DescripcionRecurso'=>'ASC'));
		if(is_array($recurso)){
			$recursos = $recurso;
		}
		else
			$recursos[0] = $recurso;
			
			$cadena = "";
			
			$cadena .= "<div class='tab-pane active' id='informes'>
         <div class='panel-group accordion accordion-dc'>";
			
			foreach($recursos as $recurso){
			$cadena .= "<div class='panel panel-warning'>
			  <div class='panel-heading'>
			    <h4 class='panel-title'>
			    <input type='hidden' name='id' value='".$recurso->getIdRecurso()."'></input>
			      <a href='#".$recurso->getRecurso()."' class='href' id='".$recurso->getIdRecurso()."' onClick='GetChild(\"".$recurso->getIdRecurso()."\")'>
			       ".$recurso->getDescripcionRecurso()."</a>
			    </h4>
			    <button type='button' class='btn btn-primary  pull-right' onClick='EliminarRecursoDirectorio(\"".$recurso->getIdRecurso()."\")'>
			     <i class='fa fa-trash-o'></i>
			   </button>  
			    <button type='button' class='btn btn-warning pull-right' data-toggle='modal' data-target='#edit' onClick='$(\"#idrecurso\").val(\"".$recurso->getIdRecurso()."\")'>
			     <i class='fa fa-pencil-square-o'></i>
			   </button>  
			    <button type='button' class='btn btn-info pull-right uphistorico' data-toggle='modal' data-target='#uploadhistorico' id='uphistorico' value='".$recurso->getIdRecurso()."' onclick='$(\"#idPadre\").val(\"".$recurso->getIdRecurso()."\")'>
			     <i class='fa fa-upload'></i>
			   </button>  
			   
			 </div>                                
			 <div class='panel-body panel-body-open' id='".$recurso->getRecurso()."'>
			             <table class='table table-bordered table-striped table-actions' >
			               <tbody id='tbody".$recurso->getIdRecurso()."'>
			</tbody>
			</table>
			</div>   
			</div>";
		}   
		
		$cadena .= "</div></div>";
		
		
		
			$this->response->setContent($cadena);
			return $this->response;
	

	}
	public function CargarHijosAction(){
		
		// Obtener valor de hidden
		$id = $this->getRequest()->getPost("idPadre");
		$recursohijo = $this->entityManager->getRepository(Recurso::class)
		->findBy(array('idRecursoPadre'=>$id), array('DescripcionRecurso'=>'ASC'));
		if(is_array($recursohijo)){
			$recursoshijos = $recursohijo;
		}
		else
			$recursoshijos[0] = $recursohijo;
	
			$cadena = "";
			
			foreach($recursoshijos as $recursohijo){
				//var_dump($recursohijo->getIdRecurso());
				$cadena .= " <tr>
    			<td>".$recursohijo->getDescripcionRecurso()."</td>
    			<td>".$recursohijo->getFechaRecurso()."</td>
    			<td> 	<div class='form-group'>
    							<select class='form-control' id='anios'>".
    							$this->ImprimirAnios($recursohijo->getIdRecurso())."
    					</select>
    					</div></td>
    			                <td><div class='row' align='middle'><div class='col-md-4'>
    				
								<button type='button' class='btn btn-info btn-block' onClick='Descargar($(\"#anios\").val())'><i class='fa fa-download'>
    							</form>
    							</i>Descargar</button></div><div class='col-md-4'>
    							<button type='button' class='btn btn-warning btn-block' data-toggle='modal' data-target='#edit' onClick='$(\"#idrecurso\").val(\"".$recursohijo->getIdRecurso()."\")'><i class='fa fa-edit'>
    							</i>Editar</button></div><div class='col-md-4'>
    							<button type='submit' class='btn btn-primary btn-block' onClick='EliminarRecurso($(\"#anios\").val())')><i class='fa fa-remove'>
    							</i>Eliminar</button></div> </div></td>
    		    </tr>";
			}
			$this->response->setContent($cadena);
			return $this->response;
			 
			 
	
	}
	
	
	public function ImprimirAnios($idpadre){
			
		$elementos = $this->entityManager->getRepository(Recurso::class)
		->findBy(array('idRecursoPadre'=>$idpadre), array('DescripcionRecurso'=>'DESC'));
			$cadena = "";
			foreach($elementos as $elemento){
						$cadena .= "<option value ='".$elemento->getIdRecurso()."'>".$elemento->getRecurso()."</option>";
			}
			$this->response->setContent($cadena);
			return $this->response;
	}
	public function PoblarHijosAction(){  // Poblar cboArchivos
		
		$id = $this->getRequest()->getPost("idRecursoArchivos");
		
		
		$recursohijo = $this->entityManager->getRepository(Recurso::class)
		->findBy(array('idRecursoPadre'=>$id), array('DescripcionRecurso'=>'ASC'));
		if(is_array($recursohijo)){
			$recursoshijos = $recursohijo;
		}
		else
			$recursoshijos[0] = $recursohijo;
		
			$cadena = "";
				
			foreach($recursoshijos as $recursohijo){
				//var_dump($recursohijo->getIdRecurso());
				$cadena .= "<option value='".$recursohijo->getIdRecurso()."'>".$recursohijo->getDescripcionRecurso()."</option>";
			
			}

			$this->response->setContent($cadena);
			return $this->response;
	}
	
	
	public function imprimirTabsAction(){
		$idPadre = $this->getRequest()->getPost('idPadre');
		$recursohijo = $this->entityManager->getRepository(Recurso::class)
		->findBy(array('idRecursoPadre' => $idPadre));
		if(is_array($recursohijo)){
			$recursoshijos = $recursohijo;
		}
		else 
			$recursoshijos[0] = $recursohijo;
		$cadena = "";
		
		foreach ($recursoshijos as $recursohijo){
			$cadena .= "<li><a href='#".$recursohijo->getIdRecurso()."' data-toggle='tab'>".$recursohijo->getDescripcionRecurso()."</a></li>";
		}
		
		$this->response->setContent($cadena);
		return $this->response;
	}
    
    
}
