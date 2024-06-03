<?php

namespace BeeDelivery\RD;

use BeeDelivery\RD\Utils\Helpers;
use BeeDelivery\RD\Utils\MessageTypeRD;
use BeeDelivery\RD\Utils\StopSeqRD;
use BeeDelivery\RD\Utils\TrackingEnumRD;
use Google\Cloud\PubSub\MessageBuilder;

class Trackings
{
    use Helpers;

    protected $pubsub;

    protected $baseTracking;

    /*
     * Create a new Connection instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->pubsub = $this->pubSubGoogle();
        $this->baseTracking = $this->prepareBaseTracking($data);
    }

    private function tracking($messageData)
    {
        try {
            $topic = $this->pubsub->topic(config('rd.inbound_tracking_response'));
            return $topic->publish((new MessageBuilder)->setData(json_encode($messageData))->build());
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    private function prepareBaseTracking($data)
    {
        return [
            'Address1' => $data->Stop[1]->FacilityAddress->Address1 ?? null,
            'Address2' => $data->Stop[1]->FacilityAddress->Address2 ?? null,
            'CarrierId' => 'BEE',
            'City' => $data->Stop[1]->FacilityAddress->City ?? null,
            'CountryId' => $data->Stop[1]->FacilityAddress->Country ?? null,
            'Latitude' => $data->Stop[1]->Latitude ?? null,
            'Longitude' => $data->Stop[1]->Longitude ?? null,
            'OrgId' => 'RD-RaiaDrogasil-SA',
            'PostalCode' => $data->Stop[1]->FacilityAddress->PostalCode,
            'ReceivedTimeStamp' => now('UTC')->toDateTimeLocalString(),
            'ReceivedTimeZone' => 'Brazil/East',
            'ShipmentId' => $data->ShipmentId,
            'SourceType' => 'API',
            'StateId' => $data->Stop[1]->FacilityAddress->State ?? null,
            'TimeZone' => 'Brazil/East',
            'TrackingEventTimeStamp' => now('UTC')->toDateTimeLocalString(),
            'TrackingReference' => $data->ShipmentId,
            'TrackingType' => 'Shipment',
            'TransportationOrderId' => $data->ShipmentId,
            'Extended' => [
                'Pedido' => $data->ShipmentId,
            ]
        ];
    }

    public function integrated($deliveryManTracking)
    {
        $messageData = [
            'MessageComments' => $deliveryManTracking,
            'MessageName' => 'Integrado',
            'MessageType' => MessageTypeRD::INTEGRATED,
            'StopSeq' => StopSeqRD::INTEGRATED,
            'TrackingReasonCodeId' => TrackingEnumRD::INTEGRATED,
        ];
        $data = array_merge($this->baseTracking, $messageData);
        return ['tracinkg' => $this->tracking($data), 'data' => $data];
    }

    public function quotation($deliveryPrice)
    {
        $messageData = [
            'MessageComments' => $deliveryPrice,
            'MessageName' => 'Preço da entrega',
            'MessageType' => MessageTypeRD::QUOTATION,
            'StopSeq' => StopSeqRD::QUOTATION,
            'TrackingReasonCodeId' => TrackingEnumRD::QUOTATION,
        ];

        $data = array_merge($this->baseTracking, $messageData);
        return ['tracinkg' => $this->tracking($data), 'data' => $data];
    }

    public function onPickupRoute()
    {
        $messageData = [
            'MessageComments' => now('UTC')->toDateTimeLocalString(),
            'MessageName' => 'Em rota de coleta',
            'MessageType' => MessageTypeRD::ON_PICKUP_ROUTE,
            'StopSeq' => StopSeqRD::ON_PICKUP_ROUTE,
            'TrackingReasonCodeId' => TrackingEnumRD::ON_PICKUP_ROUTE,
        ];

        $data = array_merge($this->baseTracking, $messageData);
        return ['tracinkg' => $this->tracking($data), 'data' => $data];
    }

    public function arrivalAtPickup()
    {
        $messageData = [
            'MessageComments' => now('UTC')->toDateTimeLocalString(),
            'MessageName' => 'Chegada no ponto de coleta',
            'MessageType' => MessageTypeRD::ARRIVAL_AT_PICKUP,
            'StopSeq' => StopSeqRD::ARRIVAL_AT_PICKUP,
            'TrackingReasonCodeId' => TrackingEnumRD::ARRIVAL_AT_PICKUP,
        ];

        $data = array_merge($this->baseTracking, $messageData);
        return ['tracinkg' => $this->tracking($data), 'data' => $data];
    }

    public function dispatched()
    {
        $messageData = [
            'MessageComments' => now('UTC')->toDateTimeLocalString(),
            'MessageName' => 'Despachado',
            'MessageType' => MessageTypeRD::DISPATCHED,
            'StopSeq' => StopSeqRD::DISPATCHED,
            'TrackingReasonCodeId' => TrackingEnumRD::DISPATCHED,
        ];

        $data = array_merge($this->baseTracking, $messageData);
        return ['tracinkg' => $this->tracking($data), 'data' => $data];
    }

    public function arrivalAtDelivery()
    {
        $messageData = [
            'MessageComments' => now('UTC')->toDateTimeLocalString(),
            'MessageName' => 'Chegada no destino do cliente',
            'MessageType' => MessageTypeRD::ARRIVAL_AT_DELIVERY,
            'StopSeq' => StopSeqRD::ARRIVAL_AT_DELIVERY,
            'TrackingReasonCodeId' => TrackingEnumRD::ARRIVAL_AT_DELIVERY,
        ];

        $data = array_merge($this->baseTracking, $messageData);
        return ['tracinkg' => $this->tracking($data), 'data' => $data];
    }

    public function successulDelivery()
    {
        $messageData = [
            'MessageComments' => now('UTC')->toDateTimeLocalString(),
            'MessageName' => 'Entrega realizada com sucesso',
            'MessageType' => MessageTypeRD::SUCCESSFUL_DELIVERY,
            'StopSeq' => StopSeqRD::SUCCESSFUL_DELIVERY,
            'TrackingReasonCodeId' => TrackingEnumRD::SUCCESSFUL_DELIVERY,
        ];

        $data = array_merge($this->baseTracking, $messageData);
        return ['tracinkg' => $this->tracking($data), 'data' => $data];
    }

    public function canceledDelivery($reasson)
    {
        $messageData = [
            'MessageComments' => $reasson,
            'MessageName' => 'Entrega cancelada - ' . $reasson,
            'MessageType' => MessageTypeRD::CANCELED_DELIVERY,
            'StopSeq' => StopSeqRD::CANCELED_DELIVERY,
            'TrackingReasonCodeId' => TrackingEnumRD::CANCELED_DELIVERY,
        ];

        $data = array_merge($this->baseTracking, $messageData);
        return ['tracinkg' => $this->tracking($data), 'data' => $data];
    }

    public function returned($reasson)
    {
        $messageData = [
            'MessageComments' => $reasson,
            'MessageName' => 'Pedido devolvido em loja - ' . $reasson,
            'MessageType' => MessageTypeRD::RETURNED,
            'StopSeq' => StopSeqRD::RETURNED,
            'TrackingReasonCodeId' => TrackingEnumRD::RETURNED,
        ];

        $data = array_merge($this->baseTracking, $messageData);
        return ['tracinkg' => $this->tracking($data), 'data' => $data];
    }

    public function reject($reasson)
    {
        $messageData = [
            'MessageComments' => $reasson,
            'MessageName' => 'Pedido recusado - ' . $reasson,
            'MessageType' => MessageTypeRD::REJECTED_BY_CARRIER,
            'StopSeq' => StopSeqRD::REJECTED_BY_CARRIER,
            'TrackingReasonCodeId' => TrackingEnumRD::REJECTED_BY_CARRIER,
        ];

        $data = array_merge($this->baseTracking, $messageData);
        return ['tracinkg' => $this->tracking($data), 'data' => $data];
    }
}
