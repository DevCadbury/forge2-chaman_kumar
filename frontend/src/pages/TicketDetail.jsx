import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api } from '../api/client.js';
import Avatar from '../components/Avatar.jsx';
import Badge from '../components/Badge.jsx';
import Spinner from '../components/Spinner.jsx';
import { useAuth } from '../context/AuthContext.jsx';
import {
  PRIORITY_LABELS,
  PRIORITY_STYLES,
  STATUS_LABELS,
  STATUS_STYLES,
  relativeTime,
} from '../lib/format.js';
import SlaBadge from '../components/SlaBadge.jsx';

export default function TicketDetail() {
  const { id } = useParams();
  const { user } = useAuth();
  const isStaff = user?.role === 'admin' || user?.role === 'agent';
  const [ticket, setTicket] = useState(null);
  const [comments, setComments] = useState([]);
  const [activity, setActivity] = useState([]);
  const [agents, setAgents] = useState([]);
  const [loading, setLoading] = useState(true);

  const loadTicket = useCallback(async () => {
    const data = await api.get(`/tickets/${id}`);
    setTicket(data.data);
  }, [id]);

  const loadThread = useCallback(async () => {
    const [c, a] = await Promise.all([
      api.get(`/tickets/${id}/comments`),
      api.get(`/tickets/${id}/activity`),
    ]);
    setComments(c.data);
    setActivity(a.data);
  }, [id]);

  useEffect(() => {
    Promise.all([loadTicket(), loadThread()]).finally(() => setLoading(false));
  }, [loadTicket, loadThread]);

  useEffect(() => {
    if (isStaff) api.get('/agents').then((d) => setAgents(d.data)).catch(() => {});
  }, [isStaff]);

  async function patch(payload) {
    const data = await api.patch(`/tickets/${id}`, payload);
    setTicket(data.data);
    loadThread();
  }

  async function assign(assigneeId) {
    const data = await api.post(`/tickets/${id}/assign`, { assignee_id: assigneeId || null });
    setTicket(data.data);
    loadThread();
  }

  if (loading) return <Spinner className="min-h-[60vh]" />;
  if (!ticket) return <p className="text-slate-500">Ticket not found.</p>;

  return (
    <div className="mx-auto max-w-6xl">
      <Link to="/tickets" className="text-sm text-slate-500 hover:text-brand-600">← Back to tickets</Link>

      <div className="mt-3 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <div className="card p-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
              <div>
                <h1 className="text-xl font-semibold text-slate-800">{ticket.subject}</h1>
                <p className="mt-1 text-xs text-slate-400">#{ticket.id} · opened by {ticket.requester?.name} · {relativeTime(ticket.created_at)}</p>
              </div>
              <div className="flex gap-2">
                <Badge className={STATUS_STYLES[ticket.status]}>{STATUS_LABELS[ticket.status]}</Badge>
                <Badge className={PRIORITY_STYLES[ticket.priority]}>{PRIORITY_LABELS[ticket.priority]}</Badge>
              </div>
            </div>
            <p className="mt-4 whitespace-pre-line text-sm text-slate-600">{ticket.description}</p>
          </div>

          <Conversation comments={comments} />

          <ReplyBox isStaff={isStaff} onSent={() => { loadThread(); loadTicket(); }} ticketId={id} />
        </div>

        <div className="space-y-6">
          {isStaff && (
            <div className="card p-5">
              <h3 className="mb-3 text-sm font-semibold text-slate-700">Manage</h3>
              <label className="label">Status</label>
              <select className="input mb-3" value={ticket.status} onChange={(e) => patch({ status: e.target.value })}>
                {Object.entries(STATUS_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
              </select>
              <label className="label">Priority</label>
              <select className="input mb-3" value={ticket.priority} onChange={(e) => patch({ priority: e.target.value })}>
                {Object.entries(PRIORITY_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
              </select>
              <label className="label">Assignee</label>
              <select className="input" value={ticket.assignee_id || ''} onChange={(e) => assign(e.target.value)}>
                <option value="">Unassigned</option>
                {agents.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
              </select>
            </div>
          )}

          <SlaBadge sla={ticket.sla} resolved={!!ticket.resolved_at} />

          <ActivityPanel activity={activity} />
        </div>
      </div>
    </div>
  );
}

function Conversation({ comments }) {
  if (comments.length === 0) {
    return <p className="mt-6 text-sm text-slate-400">No replies yet.</p>;
  }
  return (
    <div className="mt-6 space-y-4">
      {comments.map((c) => (
        <div key={c.id} className={`card p-4 ${c.is_internal ? 'border-amber-200 bg-amber-50' : ''}`}>
          <div className="flex items-center gap-3">
            <Avatar name={c.author?.name} size="sm" />
            <div className="flex-1">
              <p className="text-sm font-medium text-slate-700">{c.author?.name}</p>
              <p className="text-xs text-slate-400">{relativeTime(c.created_at)}</p>
            </div>
            {c.is_internal && <Badge className="bg-amber-200 text-amber-800">Internal note</Badge>}
          </div>
          <p className="mt-3 whitespace-pre-line text-sm text-slate-600">{c.body}</p>
        </div>
      ))}
    </div>
  );
}

function ReplyBox({ isStaff, ticketId, onSent }) {
  const [body, setBody] = useState('');
  const [internal, setInternal] = useState(false);
  const [busy, setBusy] = useState(false);

  async function send(e) {
    e.preventDefault();
    if (!body.trim()) return;
    setBusy(true);
    try {
      await api.post(`/tickets/${ticketId}/comments`, { body, is_internal: internal });
      setBody('');
      setInternal(false);
      onSent();
    } finally {
      setBusy(false);
    }
  }

  return (
    <form onSubmit={send} className="card mt-4 p-4">
      <textarea
        className="input min-h-[90px]"
        placeholder={internal ? 'Write an internal note…' : 'Write a reply…'}
        value={body}
        onChange={(e) => setBody(e.target.value)}
      />
      <div className="mt-3 flex items-center justify-between">
        {isStaff ? (
          <label className="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" checked={internal} onChange={(e) => setInternal(e.target.checked)} />
            Internal note
          </label>
        ) : <span />}
        <button className="btn-primary" disabled={busy}>{busy ? 'Sending…' : 'Send'}</button>
      </div>
    </form>
  );
}

function ActivityPanel({ activity }) {
  return (
    <div className="card p-5">
      <h3 className="mb-3 text-sm font-semibold text-slate-700">Activity</h3>
      {activity.length === 0 ? (
        <p className="text-sm text-slate-400">No activity yet.</p>
      ) : (
        <ul className="space-y-3">
          {activity.map((a) => (
            <li key={a.id} className="flex gap-2 text-sm">
              <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500" />
              <div>
                <p className="text-slate-600">
                  <span className="font-medium">{a.actor?.name || 'System'}</span> {describe(a)}
                </p>
                <p className="text-xs text-slate-400">{relativeTime(a.created_at)}</p>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

function describe(a) {
  switch (a.action) {
    case 'created':
      return 'created the ticket';
    case 'status_changed':
      return `changed status from ${a.meta?.from} to ${a.meta?.to}`;
    case 'priority_changed':
      return `changed priority from ${a.meta?.from} to ${a.meta?.to}`;
    case 'assigned':
      return 'assigned the ticket';
    case 'unassigned':
      return 'unassigned the ticket';
    case 'replied':
      return 'replied';
    case 'internal_note':
      return 'added an internal note';
    default:
      return a.action;
  }
}
