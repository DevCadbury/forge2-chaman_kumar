import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api/client.js';
import Badge from '../components/Badge.jsx';
import Modal from '../components/Modal.jsx';
import Spinner from '../components/Spinner.jsx';
import { useAuth } from '../context/AuthContext.jsx';
import {
  PRIORITY_LABELS,
  PRIORITY_STYLES,
  STATUS_LABELS,
  STATUS_STYLES,
  relativeTime,
} from '../lib/format.js';

export default function TicketsList() {
  const { user } = useAuth();
  const isStaff = user?.role === 'admin' || user?.role === 'agent';
  const [tickets, setTickets] = useState([]);
  const [agents, setAgents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({ status: '', priority: '', assignee: '', q: '' });
  const [showCreate, setShowCreate] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([k, v]) => v && params.append(k, v));
    try {
      const data = await api.get(`/tickets?${params.toString()}`);
      setTickets(data.data);
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    const t = setTimeout(load, 250);
    return () => clearTimeout(t);
  }, [load]);

  useEffect(() => {
    if (isStaff) api.get('/agents').then((d) => setAgents(d.data)).catch(() => {});
  }, [isStaff]);

  function setFilter(key, value) {
    setFilters((f) => ({ ...f, [key]: value }));
  }

  return (
    <div className="mx-auto max-w-6xl">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-slate-800">Tickets</h1>
          <p className="text-sm text-slate-500">Track and resolve customer requests.</p>
        </div>
        <button className="btn-primary" onClick={() => setShowCreate(true)}>
          New ticket
        </button>
      </div>

      <div className="mt-6 flex flex-wrap gap-3">
        <input
          className="input max-w-xs"
          placeholder="Search subject or description…"
          value={filters.q}
          onChange={(e) => setFilter('q', e.target.value)}
        />
        <Select value={filters.status} onChange={(e) => setFilter('status', e.target.value)} placeholder="All statuses" options={STATUS_LABELS} />
        <Select value={filters.priority} onChange={(e) => setFilter('priority', e.target.value)} placeholder="All priorities" options={PRIORITY_LABELS} />
        {isStaff && (
          <select className="input max-w-[180px]" value={filters.assignee} onChange={(e) => setFilter('assignee', e.target.value)}>
            <option value="">Any assignee</option>
            <option value="unassigned">Unassigned</option>
            {agents.map((a) => (
              <option key={a.id} value={a.id}>{a.name}</option>
            ))}
          </select>
        )}
      </div>

      <div className="mt-4 card overflow-hidden">
        {loading ? (
          <Spinner />
        ) : tickets.length === 0 ? (
          <p className="py-16 text-center text-sm text-slate-400">No tickets match your filters.</p>
        ) : (
          <table className="w-full text-sm">
            <thead className="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th className="px-4 py-3 font-medium">Subject</th>
                <th className="px-4 py-3 font-medium">Status</th>
                <th className="px-4 py-3 font-medium">Priority</th>
                <th className="px-4 py-3 font-medium">Assignee</th>
                <th className="px-4 py-3 font-medium">Updated</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50">
              {tickets.map((t) => (
                <tr key={t.id} className="hover:bg-slate-50">
                  <td className="px-4 py-3">
                    <Link to={`/tickets/${t.id}`} className="font-medium text-slate-800 hover:text-brand-600">
                      {t.subject}
                    </Link>
                    <p className="text-xs text-slate-400">#{t.id} · {t.requester?.name}</p>
                  </td>
                  <td className="px-4 py-3">
                    <Badge className={STATUS_STYLES[t.status]}>{STATUS_LABELS[t.status]}</Badge>
                  </td>
                  <td className="px-4 py-3">
                    <Badge className={PRIORITY_STYLES[t.priority]}>{PRIORITY_LABELS[t.priority]}</Badge>
                  </td>
                  <td className="px-4 py-3 text-slate-600">{t.assignee?.name || <span className="text-slate-400">Unassigned</span>}</td>
                  <td className="px-4 py-3 text-slate-400">{relativeTime(t.updated_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      <Modal open={showCreate} onClose={() => setShowCreate(false)} title="New ticket">
        <CreateTicketForm
          onCreated={() => {
            setShowCreate(false);
            load();
          }}
        />
      </Modal>
    </div>
  );
}

function Select({ value, onChange, placeholder, options }) {
  return (
    <select className="input max-w-[180px]" value={value} onChange={onChange}>
      <option value="">{placeholder}</option>
      {Object.entries(options).map(([val, label]) => (
        <option key={val} value={val}>{label}</option>
      ))}
    </select>
  );
}

function CreateTicketForm({ onCreated }) {
  const [form, setForm] = useState({ subject: '', description: '', priority: 'medium' });
  const [busy, setBusy] = useState(false);

  async function submit(e) {
    e.preventDefault();
    setBusy(true);
    try {
      await api.post('/tickets', form);
      onCreated();
    } finally {
      setBusy(false);
    }
  }

  return (
    <form onSubmit={submit} className="space-y-4">
      <div>
        <label className="label">Subject</label>
        <input className="input" value={form.subject} onChange={(e) => setForm({ ...form, subject: e.target.value })} required />
      </div>
      <div>
        <label className="label">Description</label>
        <textarea className="input min-h-[120px]" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} required />
      </div>
      <div>
        <label className="label">Priority</label>
        <select className="input" value={form.priority} onChange={(e) => setForm({ ...form, priority: e.target.value })}>
          {Object.entries(PRIORITY_LABELS).map(([val, label]) => (
            <option key={val} value={val}>{label}</option>
          ))}
        </select>
      </div>
      <div className="flex justify-end">
        <button className="btn-primary" disabled={busy}>{busy ? 'Creating…' : 'Create ticket'}</button>
      </div>
    </form>
  );
}
