import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api/client.js';
import { relativeTime } from '../lib/format.js';

const TYPE_TEXT = {
  ticket_assigned: 'assigned to you',
  ticket_replied: 'has a new reply',
  internal_note_added: 'has a new internal note',
};

export default function NotificationBell() {
  const [open, setOpen] = useState(false);
  const [items, setItems] = useState([]);
  const ref = useRef(null);

  async function load() {
    try {
      const data = await api.get('/notifications');
      setItems(data.data);
    } catch {
      // ignore
    }
  }

  useEffect(() => {
    load();
    const timer = setInterval(load, 20000);
    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    function onClick(e) {
      if (ref.current && !ref.current.contains(e.target)) setOpen(false);
    }
    document.addEventListener('mousedown', onClick);
    return () => document.removeEventListener('mousedown', onClick);
  }, []);

  const unread = items.filter((n) => !n.read_at).length;

  async function markAll() {
    await api.post('/notifications/read-all');
    load();
  }

  return (
    <div className="relative" ref={ref}>
      <button
        onClick={() => setOpen((v) => !v)}
        className="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100"
        aria-label="Notifications"
      >
        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.8">
          <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.8 23.8 0 0 0 5.454-1.31A8.97 8.97 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.97 8.97 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24 24 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        {unread > 0 && (
          <span className="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
            {unread}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 z-20 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
          <div className="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
            <span className="text-sm font-semibold">Notifications</span>
            {unread > 0 && (
              <button onClick={markAll} className="text-xs font-medium text-brand-600 hover:underline">
                Mark all read
              </button>
            )}
          </div>
          <div className="max-h-80 overflow-y-auto">
            {items.length === 0 && (
              <p className="px-4 py-6 text-center text-sm text-slate-400">You're all caught up.</p>
            )}
            {items.map((n) => (
              <Link
                key={n.id}
                to={n.ticket_id ? `/tickets/${n.ticket_id}` : '#'}
                onClick={() => setOpen(false)}
                className={`block border-b border-slate-50 px-4 py-3 hover:bg-slate-50 ${n.read_at ? '' : 'bg-brand-50/40'}`}
              >
                <p className="text-sm text-slate-700">
                  <span className="font-medium">{n.data?.subject || 'Ticket'}</span>{' '}
                  {TYPE_TEXT[n.type] || 'updated'}
                </p>
                <p className="mt-0.5 text-xs text-slate-400">{relativeTime(n.created_at)}</p>
              </Link>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
