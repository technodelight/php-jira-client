<?php

namespace spec_domain\Technodelight\Jira\Domain\Issue;

use Exception;
use PhpSpec\ObjectBehavior;
use Technodelight\Jira\Domain\Issue\UpdateData;
use Technodelight\Jira\Domain\Transition;

class UpdateDataSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedThrough('createEmpty');
    }

    function it_can_notify_users()
    {
        $this->notifyUsers(true);
        $this->notifyUsers()->shouldReturn(true);
    }

    function it_can_have_transitions(Transition $transition)
    {
        $transition->id()->willReturn('transitionId');
        $this->transition($transition);
        $this->asArray()->shouldReturn([
            'transition' => ['id' => 'transitionId']
        ]);
    }

    function it_can_have_fields()
    {
        $this->addField('fieldName', 'fieldValue');
        $this->asArray()->shouldReturn([
            'fields' => [
                'fieldName' => 'fieldValue'
            ]
        ]);
    }

    function it_can_have_add_updates()
    {
        $this->add('fieldName', 'fieldValue');
        $this->asArray()->shouldReturn([
            'update' => [
                'fieldName' => [
                    ['add' => 'fieldValue']
                ]
            ]
        ]);
    }

    function it_can_have_set_updates()
    {
        $this->set('fieldName', 'fieldValue');
        $this->asArray()->shouldReturn([
            'update' => [
                'fieldName' => [
                    ['set' => 'fieldValue']
                ]
            ]
        ]);
    }

    function it_can_have_edit_updates()
    {
        $this->edit('fieldName', 'fieldValue');
        $this->asArray()->shouldReturn([
            'update' => [
                'fieldName' => [
                    ['edit' => 'fieldValue']
                ]
            ]
        ]);
    }

    function it_can_have_remove_updates()
    {
        $this->remove('fieldName', 'fieldValue');
        $this->asArray()->shouldReturn([
            'update' => [
                'fieldName' => [
                    ['remove' => 'fieldValue']
                ]
            ]
        ]);
    }

    function it_can_have_properties()
    {
        $this->addProperty('property', 'value');
        $this->asArray()->shouldReturn([
            'properties' => [
                ['key' => 'property', 'value' => 'value'],
            ]
        ]);
    }

    function it_can_have_fields_and_updates_mutually_exclusive()
    {
        $this->addField('fieldName', 'fieldValue');
        $this->shouldThrow(Exception::class)->during('add', ['fieldName', 'fieldValue']);

        $this->add('updateField', 'value');
        $this->shouldThrow(Exception::class)->during('addField', ['updateField', 'value']);
    }
}
