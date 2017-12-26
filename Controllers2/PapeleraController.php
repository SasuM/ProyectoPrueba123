<?php

/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
* @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
* @license   http://framework.zend.com/license/new-bsd New BSD License
*/
namespace Application\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\Papelera;
use Application\Entity\UsuarioRol;
use Doctrine\ORM\Query\ResultSetMapping;
use Zend\Session\Container;
use Zend\Json\Json;
class PapeleraController extends AbstractActionController {
	/**
	 * Entity manager.
	 *
	 * @var Doctrine\ORM\EntityManager
	 */
	private $entityManager;
	private $entityManager2;

	// Constructor method is used to inject dependencies to the controller.
	public function __construct($entityManager, $entityManager2) {
		$this->entityManager = $entityManager;
		$this->entityManager2 = $entityManager2;
	}

	public function busquedaAction(){
		$usuarioAD = $this->identity ();
		$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
		$rol = $rolusuario->getidRol ();
		if($rol == 1){
				$papelera = $this->entityManager->getRepository ( Papelera::class )->findBy ( array (), array ('fechaEliminado' => 'ASC') );
				if (is_array ( $papelera )) {
					$papeleras = $papelera;
				} else
					$papeleras [0] = $papelera;
		}
		return new ViewModel ([ 'papeleras' => $papelera] );
}

public function descargarArchivoPAction(){
	$idArchivo = $this->getEvent ()->getRouteMatch ()->getParam ( 'id' );
	$usuarioAD = $this->identity ();
	$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
	$rol = $rolusuario->getidRol ();
	if($rol == 1){
		$archivo = $this->entityManager->getRepository ( Papelera::class )->findBy ( array ('idarchivo' => $idArchivo) );
		$file = $archivo[0]->getRutaNueva();
		$nombre = $archivo[0]->getNombreArchivo();
	if (! file_exists ( $file )) { // file does not exist
		$this->getResponse ()->setStatusCode ( 404 );
		return;
	} else {
		$fp = fopen ( $file, 'rb' );
		header ( "Cache-Control: public" );
		header ( "Content-Description: File Transfer" );
		header ( 'Content-Type: application/octet-stream' );
		header ( "Content-Disposition: attachment; filename=$nombre" );
		header ( 'Expires: 0' );
		header ( 'Cache-Control: must-revalidate' );
		header ( 'Pragma: public' );
		header ( "Content-Length: " . filesize ( $file ) );
		fpassthru ( $fp );
		return $this->response;
	}
}else
	$this->getResponse ()->setStatusCode ( 404 ); // SI NO TIENE PERMISOS PARA DESCARGAR LE MANDO LA PAGINA DE ERROR
return;
}

public function eliminarArchivoPAction(){
	$idArchivo = $this->getEvent ()->getRouteMatch ()->getParam ( 'id' );
	$rutanueva = $this->entityManager->getRepository ( Papelera::class )->findBy ( array ('idarchivo' => $idArchivo	) );
	$ruta = $rutanueva[0]->getRutaNueva();
	if (! unlink ( $ruta )) {
		return;
	} else {
		$qb = $this->entityManager->createQueryBuilder ();
		$qb->delete ( 'Application\Entity\Papelera', 'u' )->where ( 'u.idarchivo = ?1' )->setParameter ( 1, $idArchivo )->getQuery ()->execute ();
		return $this->response;
	}
}

public function poblarPapeleraAction(){
	$start = $this->getRequest ()->getPost ( "start" );
	$usuarioAD = $this->identity ();
	$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
	$rol = $rolusuario->getidRol ();
	if($rol == 1){
		$limit= 10;
		$offset= $start;
		$qb = $this->entityManager->createQueryBuilder ()
		->select ( 'u' )->from ( 'Application\Entity\Papelera', 'u' )
		->setFirstResult($offset)
		->setMaxResults($limit);
		$papeleras = $qb->getQuery()->getResult();
	$arraypapelera = array();

	foreach($papeleras as $papelera){
	$arraypapelera[] = array('idArchivo' => $papelera->getIdArchivo(), 'nombreArchivo' => $papelera->getNombreArchivo(),
	'rutaAntigua' => $papelera->getRutaAntigua(),
	'rutaNueva' => $papelera->getRutaNueva(),
	'fechaEliminado' => $papelera->getFechaEliminado()
);
	}
	$arraydata = array('draw' => 1, 'recordsTotal' => 10 , 'recordsFiltered' => 36 , 'data' => $arraypapelera);
}
$json = json_encode($arraydata);
echo $json;
return $this->response;
}
}
