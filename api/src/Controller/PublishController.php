<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class PublishController extends AbstractController
{
    #[Route('/publish', name: 'publish_test')]
    public function publish(HubInterface $hub): Response
    {
        // Topic = the "channel" subscribers listen to
        $topic = 'https://playdoink.com/test';

        // The data to send (JSON string)
        $data = json_encode(['msg' => 'Hello from Symfony + DO!NK!'], JSON_THROW_ON_ERROR);

        // Create the update
        $update = new Update($topic, $data);

        // Publish to Mercure hub
        $hub->publish($update);

        return new Response('Event published to '.$topic);
    }


}
