<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Oferta;

class ExtranetController extends Controller
{
    /**
     * @Route("/login", name="extranet_login")
     */
    public function loginAction()
    {
        $authUtils = $this->get('security.authentication_utils');

        return $this->render('extranet/login.html.twig', array(
            'last_username' => $authUtils->getLastUsername(),
            'error' => $authUtils->getLastAuthenticationError(),
        ));

    }

    /**
     * @Route("/login_check", name="extranet_login_check")
     */
    public function loginCheckAction()
    {
        // el "login check" lo hace Symfony automáticamente
    }

    /**
     * @Route("/logout", name="extranet_logout")
     */
    public function logoutAction()
    {
        // el logout lo hace Symfony automáticamente
    }

    /**
     * @Route("/", name="extranet_portada")
     */
    public function portadaAction()
    {
        $em = $this->getDoctrine()->getManager();

        $tienda = $this->getUser();
        $ofertas = $em->getRepository('AppBundle:Tienda')->findOfertasRecientes($tienda->getId());

        return $this->render(':extranet:dashboard.html.twig', array(
            'ofertas_tienda' => $ofertas
        ));
    }

    /**
     * @Route("/oferta/ventas/{id}", name="extranet_oferta_ventas")
     *
     * Muestra las ventas registradas para la oferta indicada y deniega acceso a ver ofertas de otras tiendas.
     *
     * @param int $id id de la Oferta
     *
     * @return Response
     */
    public function ofertaVentasAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $oferta = $em->getRepository('AppBundle:Oferta')->findOneById($id);
        $ventas = $em->getRepository('AppBundle:Oferta')->findVentasByOferta($id);

        $authChecker = $this->get('security.authorization_checker');

        if (!$authChecker->isGranted('view', $oferta)) {
            $this->addFlash('alert-danger', 'No puede acceder a la información de una oferta de otra tienda!');

            return $this->redirectToRoute('extranet_portada');
        }

        return $this->render('extranet/ventas.html.twig', array(
            'oferta' => $oferta,
            'ventas' => $ventas,
        ));
    }

    /**
     * @Route("/oferta/nueva", name="extranet_oferta_nueva")
     *
     * Muestra el formulario para crear una nueva oferta y se encarga del
     * procesamiento de la información recibida y la creación de las nuevas
     * entidades de tipo Oferta.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function ofertaNuevaAction(Request $request)
    {
        $tienda = $this->get('security.token_storage')->getToken()->getUser();

        $oferta = Oferta::crearParaTienda($tienda);
        $formulario = $this->createForm('AppBundle\Form\OfertaType', $oferta, array(
            'accion' => 'crear_oferta'
        ));
        $formulario->handleRequest($request);

        if ($formulario->isValid()) {
            $this->get('app.manager.oferta_manager')->guardar($oferta);
            $this->addFlash('alert-success', 'Oferta creada correctamente a la espera de ser supervisada');
            return $this->redirectToRoute('extranet_portada');
        }

        return $this->render(':extranet:oferta.html.twig', array(
            'accion' => 'crear',
            'formulario' => $formulario->createView(),
        ));
    }

    /**
     * @Route("/oferta/editar/{id}", name="extranet_oferta_editar")
     */
    public function ofertaEditarAction()
    {
        return $this->render(':extranet:dashboard.html.twig');
    }

    /**
     * @Route("/perfil", name="extranet_perfil")
     *
     * Muestra el formulario para editar los datos del perfil de la tienda que está
     * logueada en la aplicación. También se encarga de procesar la información y
     * guardar las modificaciones en la base de datos a través de un servicio creado
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function perfilAction(Request $request)
    {
        $tienda = $this->getUser();
        $formulario = $this->createForm('AppBundle\Form\TiendaType', $tienda);

        $formulario->handleRequest($request);

        if ($formulario->isValid()) {
            $this->get('app.manager.tienda_manager')->guardar($tienda);
            $this->addFlash('alert-success', 'Los datos de tu perfil se han actualizado correctamente');
            return $this->redirectToRoute('extranet_portada');
        }

        return $this->render(':extranet:perfil.html.twig', array(
            'tienda' => $tienda,
            'formulario' => $formulario->createView(),
        ));
    }
}
