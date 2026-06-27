import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';
import { AuthShell } from './Login.jsx';

export default function Register() {
  const { register } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({
    organization_name: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  });
  const [errors, setErrors] = useState({});
  const [busy, setBusy] = useState(false);

  function update(key) {
    return (e) => setForm((f) => ({ ...f, [key]: e.target.value }));
  }

  async function onSubmit(e) {
    e.preventDefault();
    setErrors({});
    setBusy(true);
    try {
      await register(form);
      navigate('/tickets');
    } catch (err) {
      setErrors(err.errors || { general: ['Unable to register.'] });
    } finally {
      setBusy(false);
    }
  }

  return (
    <AuthShell title="Create your workspace" subtitle="Set up a new organization and admin account">
      <form onSubmit={onSubmit} className="space-y-4">
        {errors.general && <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{errors.general[0]}</p>}
        <Field label="Organization name" value={form.organization_name} onChange={update('organization_name')} error={errors.organization_name} />
        <Field label="Your name" value={form.name} onChange={update('name')} error={errors.name} />
        <Field label="Email" type="email" value={form.email} onChange={update('email')} error={errors.email} />
        <Field label="Password" type="password" value={form.password} onChange={update('password')} error={errors.password} />
        <Field label="Confirm password" type="password" value={form.password_confirmation} onChange={update('password_confirmation')} />
        <button className="btn-primary w-full" disabled={busy}>
          {busy ? 'Creating…' : 'Create workspace'}
        </button>
      </form>
      <p className="mt-6 text-center text-sm text-slate-500">
        Already have an account?{' '}
        <Link to="/login" className="font-medium text-brand-600 hover:underline">
          Sign in
        </Link>
      </p>
    </AuthShell>
  );
}

function Field({ label, type = 'text', value, onChange, error }) {
  return (
    <div>
      <label className="label">{label}</label>
      <input className="input" type={type} value={value} onChange={onChange} required />
      {error && <p className="mt-1 text-xs text-red-500">{error[0]}</p>}
    </div>
  );
}
