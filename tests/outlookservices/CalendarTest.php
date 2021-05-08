<?php

namespace Office365;

use DateInterval;
use DateTime;
use DateTimeZone;
use Office365\Graph\Location;
use Office365\Graph\PhysicalAddress;
use Office365\OutlookServices\Attendee;
use Office365\OutlookServices\BodyType;
use Office365\OutlookServices\EmailAddress;
use Office365\OutlookServices\Event;
use Office365\OutlookServices\ItemBody;


class CalendarTest extends GraphTestCase
{

    public function testCreateEvent()
    {
        $currentUser = self::$graphClient->getMe()->get()->executeQuery();

        /** @var Event $event */
        $event = self::$graphClient->getMe()->getEvents()->add();
        $event->setSubject("New event");
        $event->setBody(new ItemBody(BodyType::Text,"--content goes here--"));
        $location = new Location();
        $location->Address = new PhysicalAddress();
        $location->Address->City = "Helsinki";
        $location->Address->CountryOrRegion = "Finland";
        $event->setLocation($location);
        $event->getAttendees()->addChild(new Attendee(new EmailAddress($currentUser->getDisplayName(),$currentUser->getId())));
        self::$graphClient->executeQuery();
        self::assertNotNull($event->getId());
        return $event;
    }



    public function testGetCalendarView(){
        $endDateTime = (new DateTime("now",new DateTimeZone("UTC")))->add(new DateInterval('P1D'));
        $startDateTime = (new DateTime("now",new DateTimeZone("UTC")))->sub(new DateInterval('P14D'));
        $events = self::$graphClient->getMe()->getCalendar()->getCalendarView($startDateTime,$endDateTime);
        self::$graphClient->load($events);
        self::$graphClient->executeQuery();
        self::assertGreaterThanOrEqual(1,$events->getCount());
    }


    /**
     * @depends testCreateEvent
     * @param Event $event
     */
    public function testUpdateEvent(Event $event)
    {
        $location = new Location();
        $location->DisplayName = "Room A";
        $event->setLocation($location)->update()->executeQuery();

        $updatedEvent = $event->get()->executeQuery();
        self::assertNotNull($updatedEvent->getLocation());
    }


    /**
     * @depends testCreateEvent
     * @param Event $event
     */
    public function testGetEvents(Event $event){
        $events = self::$graphClient->getMe()->getEvents()->get()->executeQuery();
        $foundEvent = $events->findFirst("Id",$event->getId());
        self::assertNotEmpty($foundEvent->getProperty("Id"));
    }


    /**
     * @depends testCreateEvent
     * @param Event $event
     */
    public function testDeleteEvent(Event $event)
    {
        $event->deleteObject()->executeQuery();
        $myEvents = self::$graphClient->getMe()->getEvents()->get()->executeQuery();
        $deletedEvent = $myEvents->findFirst("Id",$event->getId());
        self::assertNull($deletedEvent);
    }

}
