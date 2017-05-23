<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function laPortadaSimpleRedirigeAUnaCiudad()
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertEquals(302, $client->getResponse()->getStatusCode(),
            'La portada redirige a la portada de una ciudad (status 302)'
        );
    }

    /**
     * @test
     */
    public function laPortadaSoloMuestraUnaOfertaActiva()
    {
        $client = static::createClient();
        $client->followRedirects(true);

        $crawler = $client->request('GET', '/');

        $ofertasActivas = $crawler->filter('.oferta-completa .boton-compra a:contains("Comprar")');

        $this->assertCount(1, $ofertasActivas,
            'La portada muestra una única oferta activa que se puede comprar'
        );
    }

    /**
     * @test
     */
    public function losUsuariosPuedenRegistrarseDesdeLaPortada()
    {
        $client = static::createClient();
        $client->followRedirects(true);

        $crawler = $client->request('GET', '/');

        $numeroEnlacesRegistrarse = $crawler->filter('html:contains("Regístrate")')->count();

        $this->assertGreaterThan(0, $numeroEnlacesRegistrarse,
            'La portada muestra al menos un enlace o botón para registrarse'
        );

    }

    /**
     * @test
     */
    public function losUsuariosAnonimosVenLaCiudadPorDefecto()
    {
        $client = static::createClient();
        $client->followRedirects(true);

        $crawler = $client->request('GET', '/');

        $ciudadPorDefecto = $client->getContainer()->getParameter(
            'app.ciudad_por_defecto'
        );
        $ciudadPortada = $crawler->filter(
            '#ciudadseleccionada option[selected="selected"]'
        )->attr('value');

        $this->assertEquals($ciudadPorDefecto, $ciudadPortada,
            'La ciudad seleccionada en la portada de un usuario anónimo es la ciudad por defecto'
        );
    }

    /**
     * @test
     */
    public function losUsuariosAnonimosNoPuedenComprar()
    {
        $client = static::createClient();
        $client->followRedirects(true);

        $crawler = $client->request('GET', '/');

        $comprar = $crawler->selectLink('Comprar')->link();
        $client->click($comprar);

        $this->assertTrue($client->getResponse()->isRedirect(),
            'Cuando un usuario anónimo intenta comprar, se le redirige al formulario de login'
        );
    }

    /**
     * @test
     */
    public function losUsuariosAnonimosDebenLoguearseParaPoderComprar()
    {
        $pathLogin = '/.*\/usuario\/login_check/';
        $client = static::createClient();
        $client->followRedirects(true);

        $crawler = $client->request('GET', '/');

        $comprar = $crawler->selectLink('Comprar')->link();
        $crawler = $client->click($comprar);

        $this->assertRegExp($pathLogin, $crawler->filter('.login form')->attr('action'),
            'Después de pulsar el botón de comprar, el usuario anónimo ve el formulario de login'
        );
    }

    /**
     * @test
     */
    public function laPortadaRequierePocasConsultasDeBaseDeDatos()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('GET', '/');

        if ($profiler = $client->getProfile()) {
            $this->assertLessThan(4, count($profiler->getCollector('db')->getQueries()),
                'La portada requiere menos de 4 consultas a la base de datos'
            );
        }
    }

    /**
     * @test
     */
    public function laPortadaSeGeneraMuyRapido()
    {
        $client = static::createClient();
        $client->enableProfiler();

        $client->request('GET', '/');

        if ($profiler = $client->getProfile()) {
            // 500 es el tiempo en milisegundos
            $this->assertLessThan(500, $profiler->getCollector('time')->getDuration(),
                'La portada se genera en menos de medio segundo'
            );
        }
    }
}
