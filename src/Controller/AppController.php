<?php

namespace App\Controller;

use App\Command\ClassStructure;
use App\Repository\CaObjectsRepository;
use App\Repository\ProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class AppController extends AbstractController
{

    /**
     * @Route("/providence/old/{oldRoute}", name="app_legacy_index", requirements={"oldRoute"=".+"})
//     * @Route("/providence", name="app_legacy_route")
     */
    public function legacyIndex(Environment $twig, RouterInterface $router, ParameterBagInterface $bag, Request $request, $oldRoute=null): Response
    {
        $root = $bag->get('kernel.project_dir') . '/public/providence';
       $result =  require_once $root . '/index.php';

        dd($result);
        dd($root, $oldRoute);

    }

    /**
     * @Route("/providence", name="app_ca_index")
     */
    public function ca_app(Request $request): Response
    {
//        return $this->redirectToRoute('app_legacy_index');// ) . '/index.php');

        return new RedirectResponse($request->getRequestUri() . '/index.php');
    }

    /**
     * @Route("/", name="app_homepage")
     */
    public function index(Request $request): Response
    {
        // test php extraction
        $finder = new Finder();
        $dir = '/home/tac/survos/ca-symfony/vendor/collectiveaccess/providence/app/lib';
        $finder->files()->in($dir)->files()->name('*.php');
        foreach ($finder as $file) {
            $classStructure = (new ClassStructure($file, $dir));
            $includes[$classStructure->getPath()][$classStructure->getNs()] = $classStructure;
        }

        return $this->render('app/homepage.html.twig', [
            'includes' => $includes
//            'profiles' => $profileRepository->getBasicData()
        ]);

//        $object = $caObjectsRepository->findOneBy([]);
//        return $this->render('app/index.html.twig', [
//            'controller_name' => 'AppController',
//        ]);
    }
}
