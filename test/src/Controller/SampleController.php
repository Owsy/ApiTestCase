<?php

/*
 * This file is part of the ApiTestCase package.
 *
 * (c) Lakion
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lakion\ApiTestCase\Test\Controller;

use Lakion\ApiTestCase\MediaTypes;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @author Łukasz Chruściel <lukasz.chrusciel@lakion.com>
 */
class SampleController extends Controller
{
    /**
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function helloWorldAction(Request $request)
    {
        $acceptFormat = $request->headers->get('Accept');

        if (MediaTypes::JSON === $acceptFormat) {
            return new JsonResponse(['message' => 'Hello ApiTestCase World!']);
        }

        $content = '<?xml version="1.0" encoding="UTF-8"?><greetings>Hello world!</greetings>';

        $response = new Response($content);
        $response->headers->set('Content-Type', MediaTypes::XML);

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function useThirdPartyApiAction(Request $request)
    {
        $acceptFormat = $request->headers->get('Accept');
        $content = $this->get('app.third_party_api_client')->getInventory();

        if (MediaTypes::JSON === $acceptFormat) {
            return new JsonResponse($content);
        }

        $content = sprintf('<?xml version="1.0" encoding="UTF-8"?><message>%s</message>', $content['message']);

        $response = new Response($content);
        $response->headers->set('Content-Type', MediaTypes::XML);

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function productIndexAction(Request $request)
    {
        $productRepository = $this->getDoctrine()->getRepository('ApiTestCase:Product');
        $products = $productRepository->findAll();

        return $this->respond($request, $products);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function showAction(Request $request)
    {
        $productRepository = $this->getDoctrine()->getRepository('ApiTestCase:Product');
        $product = $productRepository->find($request->get('id'));

        if (!$product) {
            throw $this->createNotFoundException();
        }

        return $this->respond($request, $product);
    }

    /**
     * @param Request $request
     * @param mixed $data
     *
     * @return Response
     */
    private function respond(Request $request, $data)
    {
        $serializer = $this->createSerializer();
        $acceptFormat = $request->headers->get('Accept');

        if (MediaTypes::XML === $acceptFormat) {
            $content = $serializer->serialize($data, MediaTypes::getType(MediaTypes::XML));

            $response = new Response($content);
            $response->headers->set('Content-Type', MediaTypes::XML);

            return $response;
        }

        if (MediaTypes::JSON === $acceptFormat) {
            $content = $serializer->serialize($data, MediaTypes::getType(MediaTypes::JSON));
            $response = new Response($content);
            $response->headers->set('Content-Type', MediaTypes::JSON);

            return $response;
        }
    }

    /**
     * @return Serializer
     */
    private function createSerializer()
    {
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);

        return $serializer;
    }
}
