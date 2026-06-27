import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';
import Avatar from './Avatar.jsx';
import NotificationBell from './NotificationBell.jsx';

const NAV = [
  { to: '/tickets', label: 'Tickets', icon: 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5' },
  { to: '/dashboard', label: 'Dashboard', icon: 'M3 13.5 12 4l9 9.5M5.25 11.5V20h13.5v-8.5' },
];

export default function Layout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  async function handleLogout() {
    await logout();
    navigate('/login');
  }

  return (
    <div className="flex min-h-screen">
      <aside className="hidden w-60 shrink-0 flex-col border-r border-slate-200 bg-white px-4 py-6 md:flex">
        <div className="flex items-center gap-2 px-2">
          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white font-bold">P</div>
          <span className="text-lg font-semibold">PulseDesk</span>
        </div>
        <nav className="mt-8 flex flex-col gap-1">
          {NAV.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              className={({ isActive }) =>
                `flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium ${
                  isActive ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50'
                }`
              }
            >
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.8">
                <path strokeLinecap="round" strokeLinejoin="round" d={item.icon} />
              </svg>
              {item.label}
            </NavLink>
          ))}
        </nav>
      </aside>

      <div className="flex flex-1 flex-col">
        <header className="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-3">
          <div className="md:hidden text-lg font-semibold">PulseDesk</div>
          <div className="flex flex-1 items-center justify-end gap-3">
            <NotificationBell />
            <div className="flex items-center gap-2">
              <Avatar name={user?.name} size="sm" />
              <div className="hidden text-right sm:block">
                <p className="text-sm font-medium leading-tight">{user?.name}</p>
                <p className="text-xs capitalize text-slate-400">{user?.role}</p>
              </div>
            </div>
            <button onClick={handleLogout} className="btn-ghost px-3 py-1.5 text-xs">
              Sign out
            </button>
          </div>
        </header>

        <main className="flex-1 overflow-y-auto px-6 py-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
