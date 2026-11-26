<?php

namespace Everest\Http\Controllers\Api\Client;

use Everest\Models\Ticket;
use Illuminate\Http\Request;
use Everest\Facades\Activity;
use Illuminate\Http\Response;
use Everest\Models\TicketMessage;
use Everest\Exceptions\DisplayException;
use Everest\Http\Requests\Api\Client\ClientApiRequest;
use Everest\Transformers\Api\Client\TicketTransformer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Everest\Http\Requests\Api\Client\Account\StoreTicketRequest;
use Everest\Http\Requests\Api\Client\Account\AddTicketMessageRequest;

class TicketController extends ClientApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns all the tickets that have been configured for the logged-in
     * user account.
     */
    public function index(ClientApiRequest $request): array
    {
        $this->assertModuleEnabled();

        return $this->transform($request->user()->tickets, TicketTransformer::class);
    }

    /**
     * Stores a new Ticket for the authenticated user's account.
     */
    public function store(StoreTicketRequest $request): array
    {
        $this->assertModuleEnabled('You cannot create a ticket as the module is disabled.');

        $max_count = (int) config('modules.tickets.max_count');

        if ($request->user()->tickets()->count() >= $max_count) {
            throw new DisplayException("You have reached the ticket count per user of {$max_count}.");
        }

        $ticket = $request->user()->tickets()->create([
            'title' => $request->input('title'),
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->input('message'),
        ]);

        Activity::event('user:ticket.create')
            ->subject($ticket)
            ->log();

        return $this->transform($ticket, TicketTransformer::class);
    }

    /**
     * View a ticket and its associated messages.
     */
    public function view(Ticket $ticket, Request $request): array
    {
        $this->assertModuleEnabled();
        $this->assertOwnsTicket($ticket, $request);

        return $this->transform($ticket, TicketTransformer::class);
    }

    /**
     * Add a message to a ticket.
     */
    public function message(Ticket $ticket, AddTicketMessageRequest $request): array
    {
        $this->assertModuleEnabled();
        $this->assertOwnsTicket($ticket, $request);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->input('message'),
        ]);

        return $this->transform($ticket, TicketTransformer::class);
    }

    /**
     * Deletes an Ticket from the user's account.
     */
    public function delete(Ticket $ticket, ClientApiRequest $request): Response
    {
        $this->assertModuleEnabled();
        $this->assertOwnsTicket($ticket, $request);

        $ticket->delete();
        TicketMessage::where('ticket_id', $ticket->id)->delete();

        Activity::event('user:ticket.delete')
            ->property('identifier', $ticket->id)
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Every client ticket endpoint is part of the tickets module, so none of them are
     * reachable while an administrator has the module turned off.
     *
     * @throws DisplayException
     */
    private function assertModuleEnabled(string $message = 'The tickets module is currently disabled.'): void
    {
        if (!boolval(config('modules.tickets.enabled'))) {
            throw new DisplayException($message);
        }
    }

    /**
     * Report another user's ticket exactly like a ticket that does not exist, so that
     * ticket IDs belonging to other accounts cannot be discovered.
     */
    private function assertOwnsTicket(Ticket $ticket, Request $request): void
    {
        if ($request->user()->id !== (int) $ticket->user_id) {
            throw (new ModelNotFoundException())->setModel(Ticket::class, [$ticket->id]);
        }
    }
}
