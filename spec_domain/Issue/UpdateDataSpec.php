<?php

namespace spec_domain\Technodelight\Jira\Domain\Issue;

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
}
