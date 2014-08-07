<?php

namespace spec\Judopay\Model;

require_once 'ModelObjectBehavior.php';

class CardPaymentSpec extends ModelObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Judopay\Model\CardPayment');
    }

    public function it_should_create_a_new_payment()
    {
        $this->beConstructedWith($this->concoctRequest());

        $modelBuilder = new \Judopay\Test\CardPaymentBuilder;
        $this->setAttributeValues(
            $modelBuilder->getAttributeValues()
        );
        $output = $this->create();

        $output->shouldBeArray();
        $output['result']->shouldEqual('Success');
    }

    public function it_should_raise_an_error_when_required_fields_are_missing()
    {
        $this->beConstructedWith($this->concoctRequest());
        $this->shouldThrow('\Judopay\Exception\ValidationError')->during('create');
    }

    // Generic model methods
    public function it_coerces_attributes_into_the_correct_data_type()
    {
        $input = array(
            'yourPaymentMetaData' => 'an unexpected string',
            'judoId' => 'judo123',
            'amount' => '123.23'
        );

        $expectedOutput = array(
            'yourPaymentMetaData' => array('an unexpected string'),
            'judoId' => 'judo123',
            'amount' => 123.23
        );

        $this->setAttributeValues($input);
        $this->getAttributeValues()->shouldEqual($expectedOutput);
    }

    public function it_should_baulk_at_very_unusual_float_values()
    {
        $input = array(
            'amount' => '123.23GBP'
        );

        $this->shouldThrow('\OutOfBoundsException')->during('setAttributeValues', array($input));
    }

    protected function concoctRequest()
    {
        $request = new \Judopay\Request($this->configuration);
        $request->setClient(
            \Judopay\SpecHelper::getMockResponseClient(
                200,
                'card_payments/create.json'
            )
        );

        return $request;
    }
}