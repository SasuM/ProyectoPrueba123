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
use Application\Entity\UsuarioRol;
use Application\Entity\RecursoRol;
use Zend\Session\Container;

class ComiteController extends AbstractActionController 
{
	/**
	 * Entity manager.
	 *
	 * @var Doctrine\ORM\EntityManager
	 */
	 private $entityManager;
	 private $entityManager2;
	 public function __construct($entityManager, $entityManager2) {
		 $this->entityManager = $entityManager;
		 $this->entityManager2 = $entityManager2;
	 }
	public function buAction() {
		// VALIDACION PARA SABER SI EL USUARIO ES ADMINISTRADOR O NO
		$usuarioAD = $this->identity ();
		$fullUrl = $_SERVER['REQUEST_URI'];
		$sessionUrl = new Container('ContainerUrl' , $sessionManager);
		$sessionUrl->url = $fullUrl;
		$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
		$rol = $rolusuario->getidRol ();
		$idtab = $this->getEvent ()->getRouteMatch ()->getParam ( 'idtab' );
		if ($idtab == null) {
			$idtab = '279';
		}
		$roleseccion = $this->entityManager->getRepository (RecursoRol::class)->findBy ( array ('Editar' => 1,'idRecurso' => $idtab ));
		$arrayroles = array();
		foreach ($roleseccion as $rolseccion){
			if($rolseccion->getidRol() != 1 && $rolseccion->getidRol() != 4 && $rolseccion->getidRol() != 5 && $rolseccion->getidRol() != 6 && $rolseccion->getidRol() != 7 && $rolseccion->getidRol() != 8 && 
			$rolseccion->getidRol() != 9 && $rolseccion->getidRol() != 10 && $rolseccion->getidRol() != 11 && $rolseccion->getidRol() != 12 && $rolseccion->getidRol() != 13 && $rolseccion->getidRol() != 14 &&
			$rolseccion->getidRol() != 15 ){
			$arrayroles[] = $rolseccion->getidRol();}

		}
		//SELECT DE USUARIOROL por ROL
		$query = $this->entityManager->createQuery('SELECT u.idUsuario FROM Application\Entity\UsuarioRol u WHERE u.idRol IN (:id)');
		$query->setParameter('id', $arrayroles);
		$listausuarios = $query->getArrayResult();

		$query2 = $this->entityManager2->createQuery('SELECT u.nombre,u.apellido FROM Application\Entity\Personal u Where u.usuarioActiveDirectory IN (:id)');
		$query2->setParameter('id',$listausuarios);
		$nombreusuarios = $query2->getResult();
		$listanombres = array();
		foreach ($nombreusuarios as $nombreusuario){
			$listanombres[] = $nombreusuario['nombre'] . ' '. $nombreusuario['apellido'];
		}
		// ---------------------------------------------------------------------------------------
		$arrayrolesusuarios = array();
		foreach ($roleseccion as $rolseccion){
			if($rolseccion->getidRol() == 5 || $rolseccion->getidRol()==6  || $rolseccion->getidRol()==7   || $rolseccion->getidRol()==8   || $rolseccion->getidRol()==9 
			|| $rolseccion->getidRol()==10   || $rolseccion->getidRol()==11   || $rolseccion->getidRol()==12   || $rolseccion->getidRol()==13   || $rolseccion->getidRol()==14 
			|| $rolseccion->getidRol()==15  ){
			$arrayrolesusuarios[] = $rolseccion->getidRol();}
		}

		$query3 = $this->entityManager->createQuery('SELECT u.idUsuario FROM Application\Entity\UsuarioRol u WHERE u.idRol IN (:id)');
		$query3->setParameter('id', $arrayrolesusuarios);
		$listausuarios2 = $query3->getArrayResult();
	
		$query4 = $this->entityManager2->createQuery('SELECT u.nombre,u.apellido FROM Application\Entity\Personal u Where u.usuarioActiveDirectory IN (:id)');
		$query4->setParameter('id',$listausuarios2);
		$nombreusuariosR = $query4->getResult();
		$listanombresR = array();
		foreach ($nombreusuariosR as $nombreusuarioR){
			$listanombresR[] = $nombreusuarioR['nombre'] . ' '. $nombreusuarioR['apellido'];
		}
		$verificarol = $this->entityManager->getRepository ( RecursoRol::class )->findBy ( array ('idRol' => $rol,'idRecurso' => $idtab) );
		$descargar = $verificarol [0]->getDescargar ();
		$editar = $verificarol [0]->getEditar ();
		$eliminar = $verificarol [0]->getEliminar ();
		$viewModel = new ViewModel ();
		$viewModel->setVariables ( array ('key' => 'value' ) )->setTerminal ( true );
		$recurso = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => $idtab), array ('DescripcionRecurso' => 'ASC' ) );
		if (is_array ( $recurso )) {
			$recursos = $recurso;
		} else
			$recursos [0] = $recurso;
		return new ViewModel ( [ 'recursos' => $recurso,'idtab' => $idtab,'descargar' => $descargar,'editar' => $editar,'eliminar' => $eliminar,
		'listanombres' => $listanombres, 'listanombresR' => $listanombresR
		] );
		$response = $this->getResponse ();
		return $response;
	}
	
	// Funcion que lista todos los elementos de un directorio apartir de su idPadre o directorio padre
	public function CargarHijosAction() {
		$usuarioAD = $this->identity ();
		$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
		$rol = $rolusuario->getidRol ();
		$idtab = $this->getEvent ()->getRouteMatch ()->getParam ( 'idtab' );
		if ($idtab == null) {
			$idtab = '277';
		}
		$verificarol = $this->entityManager->getRepository ( RecursoRol::class )->findBy ( array ('idRol' => $rol,'idRecurso' => $idtab	) );
		$descargar = $verificarol [0]->getDescargar ();
		$editar = $verificarol [0]->getEditar ();
		$eliminar = $verificarol [0]->getEliminar ();
		// Obtener valor de hidden
		$id = $this->getRequest ()->getPost ( "idPadre" );
		$recursohijo = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => $id ,'flag' => '1'), array ('DescripcionRecurso' => 'DESC' ) );
		if (is_array ( $recursohijo )) {
			$recursoshijos = $recursohijo;
		} else
			$recursoshijos [0] = $recursohijo;
		$cadena = "";
		foreach ( $recursoshijos as $recursohijo ) {
			// var_dump($recursohijo->getIdRecurso());
			$fecha = $recursohijo->getFechaRecurso ();
			$nuevafecha = date ( "d/m/Y", strtotime ( $fecha ) );
			$shortDesc = strlen ( $recursohijo->getDescripcionRecurso () ) > 40 ? substr ( $recursohijo->getDescripcionRecurso (), 0, 40 ) . "..." : $recursohijo->getDescripcionRecurso ();
			$cadenaadmin .= "<tr>
    			<td class='ellipsis' data-toggle='tooltip' data-placement='right' title='' data-original-title='" . $recursohijo->getDescripcionRecurso () . "'>" . $recursohijo->getDescripcionRecurso () . "</td>
    			<td>" . $nuevafecha . "</td>
    			<td> 	<div class='form-group'>
    							<select class='form-control' id='anios" . $recursohijo->getIdRecurso () . "'>" . $this->ImprimirMeses ( $recursohijo->getIdRecurso () ) . "
    					</select>
    					</div></td>
    			                <td><div class='row' align='middle'><div class='col-md-4'>
    
								<button type='button' class='btn btn-info btn-block' onClick='Descargar($(\"#anios" . $recursohijo->getIdRecurso () . "\").val())'><i class='fa fa-download'>
    							</form>
    							</i>Descargar</button></div><div class='col-md-4'>
    							<button type='button' class='btn btn-warning btn-block' data-toggle='modal' data-target='#edit' onClick='SetEditarAction(" . $recursohijo->getIdRecurso () . ")'><i class='fa fa-edit'>
    							</i>Editar</button></div><div class='col-md-4'>
    							<button type='submit' class='btn btn-primary btn-block' onClick='EliminarRecurso($(\"#anios" . $recursohijo->getIdRecurso () . "\").val(),$(\"#anios" . $recursohijo->getIdRecurso () . " option:selected\").text())'><i class='fa fa-remove'>
    							</i>Eliminar</button></div> </div></td>
    		    </tr>";
			$cadenausuario .= "<tr>
    			<td>" . $recursohijo->getDescripcionRecurso () . "</td>
    			<td>" . $nuevafecha . "</td>
    			<td> 	<div class='form-group'>
    							<select class='form-control' id='anios" . $recursohijo->getIdRecurso () . "'>" . $this->ImprimirMeses ( $recursohijo->getIdRecurso () ) . "
    					</select>
    					</div></td>
    			                <td><div class='row' align='middle'><div class='col-md-12'>
    
								<button type='button' class='btn btn-info btn-block' onClick='Descargar($(\"#anios" . $recursohijo->getIdRecurso () . "\").val())'><i class='fa fa-download'>
    							</form>
    							</i>Descargar</button></div>
    		    </tr>";
		}
		if ($editar == false)
			$this->response->setContent ( $cadenausuario );
		else
			$this->response->setContent ( $cadenaadmin );
		return $this->response;
	}
	
	// Funcion que pobla los anios en el select años (en la tabla)
	public function ImprimirMeses($idpadre) {
		$elementos = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => $idpadre ), array ('DescripcionRecurso' => 'DESC' ) );
		$cadena = "";
		foreach ( $elementos as $elemento ) {
			$mes = $elemento->getRecurso ();
			setlocale(LC_TIME, 'spanish'); 
			if ($mes >= 1 && $mes <= 12) {
				$nombremes = strftime("%B",mktime(0, 0, 0, $mes, 1, 2000));
			}
			$cadena .= "<option value ='" . $elemento->getIdRecurso () . "'>" . $nombremes . "</option>";
		}
		if ($cadena == "") {
			$cadena .= "<option value =" . $idpadre . ">(Vacío)</option>";
		}
		$this->response->setContent ( $cadena );
		return $this->response;
	}
	// Funcion que pobla los anios en el select años (en el modal)
	public function PoblarHijosAction() {
		$id = $this->getRequest ()->getPost ( "idRecursoArchivos" );
		$recursohijo = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => $id ,'flag' => '1'), array ('DescripcionRecurso' => 'DESC') );
		if (is_array ( $recursohijo )) {
			$recursoshijos = $recursohijo;
		} else
			$recursoshijos [0] = $recursohijo;
		$cadena = "";
		foreach ( $recursoshijos as $recursohijo ) {
			$cadena .= "<option value='" . $recursohijo->getIdRecurso () . "'>" . $recursohijo->getDescripcionRecurso () . "</option>";
		}
		$this->response->setContent ( $cadena );
		return $this->response;
	}
	public function areasfuncionalesAction() {
		// VALIDACION PARA SABER SI EL USUARIO ES ADMINISTRADOR O NO
		$usuarioAD = $this->identity ();
		$fullUrl = $_SERVER['REQUEST_URI'];
		$sessionUrl = new Container('ContainerUrl' , $sessionManager);
		$sessionUrl->url = $fullUrl;
		$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
		$rol = $rolusuario->getidRol ();
		$idtab = $this->getEvent ()->getRouteMatch ()->getParam ( 'idtab' );
		if ($idtab == null) {
			$idtab = '302';
		}
		$roleseccion = $this->entityManager->getRepository (RecursoRol::class)->findBy ( array ('Editar' => 1,'idRecurso' => $idtab ));
		$arrayroles = array();
		foreach ($roleseccion as $rolseccion){
			if($rolseccion->getidRol() != 1 && $rolseccion->getidRol() != 4 && $rolseccion->getidRol() != 5 && $rolseccion->getidRol() != 6 && $rolseccion->getidRol() != 7 && $rolseccion->getidRol() != 8 && 
			$rolseccion->getidRol() != 9 && $rolseccion->getidRol() != 10 && $rolseccion->getidRol() != 11 && $rolseccion->getidRol() != 12 && $rolseccion->getidRol() != 13 && $rolseccion->getidRol() != 14 &&
			$rolseccion->getidRol() != 15 ){
			$arrayroles[] = $rolseccion->getidRol();}
		}
		//SELECT DE USUARIOROL por ROL
		$query = $this->entityManager->createQuery('SELECT u.idUsuario FROM Application\Entity\UsuarioRol u WHERE u.idRol IN (:id)');
		$query->setParameter('id', $arrayroles);
		$listausuarios = $query->getArrayResult();

		$query2 = $this->entityManager2->createQuery('SELECT u.nombre,u.apellido FROM Application\Entity\Personal u Where u.usuarioActiveDirectory IN (:id)');
		$query2->setParameter('id',$listausuarios);
		$nombreusuarios = $query2->getResult();
		$listanombres = array();
		foreach ($nombreusuarios as $nombreusuario){
			$listanombres[] = $nombreusuario['nombre'] . ' '. $nombreusuario['apellido'];
		}
		// ---------------------------------------------------------------------------------------
		$arrayrolesusuarios = array();
		foreach ($roleseccion as $rolseccion){
			if($rolseccion->getidRol() == 5 || $rolseccion->getidRol()==6  || $rolseccion->getidRol()==7   || $rolseccion->getidRol()==8   || $rolseccion->getidRol()==9 
			|| $rolseccion->getidRol()==10   || $rolseccion->getidRol()==11   || $rolseccion->getidRol()==12   || $rolseccion->getidRol()==13   || $rolseccion->getidRol()==14 
			|| $rolseccion->getidRol()==15  ){
			$arrayrolesusuarios[] = $rolseccion->getidRol();}
		}

		$query3 = $this->entityManager->createQuery('SELECT u.idUsuario FROM Application\Entity\UsuarioRol u WHERE u.idRol IN (:id)');
		$query3->setParameter('id', $arrayrolesusuarios);
		$listausuarios2 = $query3->getArrayResult();
	
		$query4 = $this->entityManager2->createQuery('SELECT u.nombre,u.apellido FROM Application\Entity\Personal u Where u.usuarioActiveDirectory IN (:id)');
		$query4->setParameter('id',$listausuarios2);
		$nombreusuariosR = $query4->getResult();

		$listanombresR = array();
		foreach ($nombreusuariosR as $nombreusuarioR){
			$listanombresR[] = $nombreusuarioR['nombre'] . ' '. $nombreusuarioR['apellido'];
		}
		$verificarol = $this->entityManager->getRepository ( RecursoRol::class )->findBy ( array ('idRol' => $rol,'idRecurso' => $idtab ) );
		$descargar = $verificarol [0]->getDescargar ();
		$editar = $verificarol [0]->getEditar ();
		$eliminar = $verificarol [0]->getEliminar ();
		$viewModel = new ViewModel ();
		$viewModel->setVariables ( array ('key' => 'value') )->setTerminal ( true );
		$recurso = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => $idtab ,'flag' => '1'), array ('FechaRecurso' => 'DESC' ) );
		if (is_array ( $recurso )) {
			$recursos = $recurso;
		} else
			$recursos [0] = $recurso;
		return new ViewModel ( [ 'recursos' => $recurso,'idtab' => $idtab,'descargar' => $descargar,'editar' => $editar,'eliminar' => $eliminar,'listanombres' => $listanombres ,'listanombresR' => $listanombresR] );
		$response = $this->getResponse ();
		return $response;
	}

	public function operacionesAction() {
		// VALIDACION PARA SABER SI EL USUARIO ES ADMINISTRADOR O NO
		$usuarioAD = $this->identity ();
		$fullUrl = $_SERVER['REQUEST_URI'];
		$sessionUrl = new Container('ContainerUrl' , $sessionManager);
		$sessionUrl->url = $fullUrl;
		$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
		$rol = $rolusuario->getidRol ();
		$idtab = $this->getEvent ()->getRouteMatch ()->getParam ( 'idtab' );
		if ($idtab == null) {
			$idtab = '323';
		}
		$roleseccion = $this->entityManager->getRepository (RecursoRol::class)->findBy ( array ('Editar' => 1,'idRecurso' => $idtab ));
		$arrayroles = array();
		foreach ($roleseccion as $rolseccion){
			if($rolseccion->getidRol() != 1 && $rolseccion->getidRol() != 4 && $rolseccion->getidRol() != 5 && $rolseccion->getidRol() != 6 && $rolseccion->getidRol() != 7 && $rolseccion->getidRol() != 8 && 
			$rolseccion->getidRol() != 9 && $rolseccion->getidRol() != 10 && $rolseccion->getidRol() != 11 && $rolseccion->getidRol() != 12 && $rolseccion->getidRol() != 13 && $rolseccion->getidRol() != 14 &&
			$rolseccion->getidRol() != 15 ){
			$arrayroles[] = $rolseccion->getidRol();}
		}

		//SELECT DE USUARIOROL por ROL
		$query = $this->entityManager->createQuery('SELECT u.idUsuario FROM Application\Entity\UsuarioRol u WHERE u.idRol IN (:id)');
		$query->setParameter('id', $arrayroles);
		$listausuarios = $query->getArrayResult();

		$query2 = $this->entityManager2->createQuery('SELECT u.nombre,u.apellido FROM Application\Entity\Personal u Where u.usuarioActiveDirectory IN (:id)');
		$query2->setParameter('id',$listausuarios);
		$nombreusuarios = $query2->getResult();
		$listanombres = array();
		foreach ($nombreusuarios as $nombreusuario){
			$listanombres[] = $nombreusuario['nombre'] . ' '. $nombreusuario['apellido'];
		}
		// ---------------------------------------------------------------------------------------
		$arrayrolesusuarios = array();
		foreach ($roleseccion as $rolseccion){
			if($rolseccion->getidRol() == 5 || $rolseccion->getidRol()==6  || $rolseccion->getidRol()==7   || $rolseccion->getidRol()==8   || $rolseccion->getidRol()==9 
			|| $rolseccion->getidRol()==10   || $rolseccion->getidRol()==11   || $rolseccion->getidRol()==12   || $rolseccion->getidRol()==13   || $rolseccion->getidRol()==14 
			|| $rolseccion->getidRol()==15  ){
			$arrayrolesusuarios[] = $rolseccion->getidRol();}
		}
		$query3 = $this->entityManager->createQuery('SELECT u.idUsuario FROM Application\Entity\UsuarioRol u WHERE u.idRol IN (:id)');
		$query3->setParameter('id', $arrayrolesusuarios);
		$listausuarios2 = $query3->getArrayResult();
	
		$query4 = $this->entityManager2->createQuery('SELECT u.nombre,u.apellido FROM Application\Entity\Personal u Where u.usuarioActiveDirectory IN (:id)');
		$query4->setParameter('id',$listausuarios2);
		$nombreusuariosR = $query4->getResult();
		$listanombresR = array();
		foreach ($nombreusuariosR as $nombreusuarioR){
			$listanombresR[] = $nombreusuarioR['nombre'] . ' '. $nombreusuarioR['apellido'];
		}
		$verificarol = $this->entityManager->getRepository ( RecursoRol::class )->findBy ( array ('idRol' => $rol,'idRecurso' => $idtab	) );
		$descargar = $verificarol [0]->getDescargar ();
		$editar = $verificarol [0]->getEditar ();
		$eliminar = $verificarol [0]->getEliminar ();
		$viewModel = new ViewModel ();
		$viewModel->setVariables ( array ('key' => 'value') )->setTerminal ( true );
		$recurso = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => $idtab), array ('DescripcionRecurso' => 'ASC' ) );
		if (is_array ( $recurso )) {
			$recursos = $recurso;
		} else
			$recursos [0] = $recurso;
		return new ViewModel ( [ 'recursos' => $recurso,'idtab' => $idtab,'descargar' => $descargar,'editar' => $editar,'eliminar' => $eliminar,'listanombres' => $listanombres ,'listanombresR' => $listanombresR] );
		$response = $this->getResponse ();
		return $response;
	}

	public function PoblarSelectMesesAction() {
		$id = $this->getRequest ()->getPost ( "idMes" );
		$recursohijo = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => $id) );
		if (is_array ( $recursohijo )) {
			$recursoshijos = $recursohijo;
		} else {
			$recursoshijos [0] = $recursohijo;
		}
		$cadena = "";
		$cadenavacia = "";
		if (empty ( $recursohijo )) {
			$cadenavacia = "<option value='-1'>No hay Años</option";
			$this->response->setContent ( $cadenavacia );
			return $this->response;
		} else {
			$arrayMeses = range ( 1, 12 );
			$arrayMesesBD = array ();
			foreach ( $recursoshijos as $recursohijo ) {
				array_push ( $arrayMesesBD, $recursohijo->getRecurso () );
			}
			$arrayMeses = array_diff ( $arrayMeses, $arrayMesesBD );
			foreach ( $arrayMeses as $mes ) {
				$cadena .= "<option value='" . $mes . "'>" . $this->ConvertirAMes ( $mes ) . "</option>";
			}
			$this->response->setContent ( $cadena );
			return $this->response;
		}
	}

	public function ConvertirAMes($mesnumero) {
		$mes = $mesnumero;
		setlocale(LC_TIME, 'spanish'); 
		if ($mes >= 1 && $mes <= 12) {
			$nombremes = strftime("%B",mktime(0, 0, 0, $mes, 1, 2000));
		}
		return $nombremes;
	}
	
}
