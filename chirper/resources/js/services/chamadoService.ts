import type { CreateHelpdeskTicket, HelpdeskTicket } from '../types/helpdesk';
import { createTicket, fetchTickets } from './ticketService';

// Backward compatibility wrapper during migration to Ticket naming.
export async function fetchChamados(): Promise<HelpdeskTicket[]> {
    return fetchTickets();
}

// Backward compatibility wrapper during migration to Ticket naming.
export async function createChamado(payload: CreateHelpdeskTicket): Promise<{
    message: string;
    data: unknown;
}> {
    return createTicket(payload);
}
