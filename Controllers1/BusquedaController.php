<?php

/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
* @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
* @license   http://framework.zend.com/license/new-bsd New BSD License
*/
namespace Application\Controller;;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\UsuarioRol;
use Application\Entity\Recurso;
use Doctrine\ORM\Query\ResultSetMapping;
use Zend\Session\Container;


class BusquedaController extends AbstractActionController {
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
	
	//Primera vista de la Busqueda de Archivos
	public function archivoAction(){
		$usuarioAD = $this->identity ();
		$iddir = $this->getEvent ()->getRouteMatch ()->getParam ( 'id' );
		$nombre = $this->getRequest ()->getPost ( "busqueda" );
		if($nombre != null){
			$sessionUrl = new Container('PreviousUrlB' , $sessionManager);
			$sessionUrl->url = $nombre;
		}
		if($iddir == null){
		$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
		$rol = $rolusuario->getidRol ();
		$rsm = new ResultSetMapping ();
		$rsm->addEntityResult ( 'Application\Entity\Recurso', 'r' );
		$rsm->addFieldResult ( 'r', 'idRecursoPadre', 'idRecursoPadre' );
		$rsm->addFieldResult ( 'r', 'idRecurso', 'idRecurso' );
		$rsm->addFieldResult ( 'r', 'DescripcionRecurso', 'DescripcionRecurso' );
		$rsm->addFieldResult ( 'r', 'TipoRecurso', 'TipoRecurso' );
		$rsm->addFieldResult ( 'r', 'ExtensionRecurso', 'ExtensionRecurso' );
		$rsm->addFieldResult ( 'r', 'FechaRecurso', 'FechaRecurso' );
		$rsm->addFieldResult ( 'r', 'Recurso' , 'Recurso' );
		$em = $this->entityManager;
		$qb = $em->createNativeQuery ( "CALL pa_BuscarRecursoSEL ('".$nombre."')", $rsm );
		$qb->execute ();
		$em->flush ();
		$archivo = $qb->getResult();
		}
		else{
			$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
			$rol = $rolusuario->getidRol ();
			$rsm = new ResultSetMapping ();
			$rsm->addEntityResult ( 'Application\Entity\Recurso', 'r' );
			$rsm->addFieldResult ( 'r', 'idRecursoPadre', 'idRecursoPadre' );
			$rsm->addFieldResult ( 'r', 'idRecurso', 'idRecurso' );
			$rsm->addFieldResult ( 'r', 'DescripcionRecurso', 'DescripcionRecurso' );
			$rsm->addFieldResult ( 'r', 'TipoRecurso', 'TipoRecurso' );
			$rsm->addFieldResult ( 'r', 'ExtensionRecurso', 'ExtensionRecurso' );
			$rsm->addFieldResult ( 'r', 'FechaRecurso', 'FechaRecurso' );
			$rsm->addFieldResult ( 'r', 'Recurso' , 'Recurso' );
			$em = $this->entityManager;
			$qb = $em->createNativeQuery ( "CALL pa_BuscarRecursobyIdSEL (".$iddir.")", $rsm );
			$qb->execute ();
			$em->flush ();
			$archivo = $qb->getResult();
		}
		return new ViewModel ( [ 'archivos' => $archivo ] );
	}

	public function getNombreDirectorio($idrecurso) {
		$qb = $this->entityManager->createQueryBuilder ();
		$q = $qb->select ( 'u.DescripcionRecurso' )->from ( 'Application\Entity\Recurso', 'u' )->where ( 'u.idRecurso = ?1' )->setParameter ( 1, $idrecurso )->getQuery ();
		$p = $q->execute ();
		$p = $p [0];
		$recurso = implode ( '.', $p );
		return $recurso;
	}
	
	public function crearRuta($idruta) {
		$idPadre = $idruta;
		$ruta = "";
		while ( $idPadre != Null ) {
			$recursopadre = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idPadre
			) );
			if (is_array ( $recursopadre )) {
				$recursospadre = $recursopadre;
			} else
				$recursospadre [0] = $recursopadre;
				foreach ( $recursospadre as $recursopadre ) {
					$padre = $recursopadre->getIdRecursoPadre ();
					$nombre = $recursopadre->getRecurso ();
					$idPadre = $padre;
					$ruta = $nombre . "/" . $ruta;
				}
				if ($idPadre == '0') {
					break;
				}
		}
		return $ruta;
	}

	public function ConvertirAMes($mesnumero) {
		$mes = $mesnumero;
		if ($mes >= 1 && mess <= 12) {
			$nombremes = date ( "F", strtotime ( "2001-" . $mes . "-01" ) );
		}
		return $nombremes;
	}
	
	public function ObtenerIdPadre($idHijo) {
		$qb = $this->entityManager->createQueryBuilder ();
		$q = $qb->select ( 'u.idRecursoPadre' )->from ( 'Application\Entity\Recurso', 'u' )->where ( 'u.idRecurso = ?1' )->setParameter ( 1, $idHijo )->getQuery ();
		$p = $q->execute ();
		$p = $p [0];
		$idpadre = implode ( '.', $p );
		return $idpadre;
	}
}
