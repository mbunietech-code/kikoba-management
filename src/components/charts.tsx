import {
  Area, AreaChart, Bar, BarChart, CartesianGrid, Cell, Pie, PieChart, ResponsiveContainer,
  Tooltip, XAxis, YAxis,
} from 'recharts'
import { formatMoney } from '@/lib/format'

const AXIS = { fontSize: 11, fill: '#94a3b8' }
const GRID = '#e2e8f0'

const tooltipStyle = {
  borderRadius: 12,
  border: '1px solid #e2e8f0',
  boxShadow: '0 8px 32px -8px rgb(15 23 42 / 0.18)',
  fontSize: 12,
}

export function AreaTrend({
  data,
  keys,
  colors,
}: {
  data: Record<string, number | string>[]
  keys: string[]
  colors: string[]
}) {
  return (
    <ResponsiveContainer width="100%" height={260}>
      <AreaChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: -8 }}>
        <defs>
          {keys.map((k, i) => (
            <linearGradient key={k} id={`grad-${k}`} x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor={colors[i]} stopOpacity={0.25} />
              <stop offset="100%" stopColor={colors[i]} stopOpacity={0} />
            </linearGradient>
          ))}
        </defs>
        <CartesianGrid stroke={GRID} strokeDasharray="3 3" vertical={false} />
        <XAxis dataKey="month" tick={AXIS} axisLine={false} tickLine={false} />
        <YAxis tick={AXIS} axisLine={false} tickLine={false} tickFormatter={(v) => formatMoney(Number(v), { compact: true, withSymbol: false })} width={48} />
        <Tooltip contentStyle={tooltipStyle} formatter={(v: number) => formatMoney(v)} />
        {keys.map((k, i) => (
          <Area key={k} type="monotone" dataKey={k} stroke={colors[i]} strokeWidth={2} fill={`url(#grad-${k})`} />
        ))}
      </AreaChart>
    </ResponsiveContainer>
  )
}

export function MiniBars({
  data,
  color = '#115e59',
}: {
  data: Record<string, number | string>[]
  color?: string
}) {
  return (
    <ResponsiveContainer width="100%" height={220}>
      <BarChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: -8 }}>
        <CartesianGrid stroke={GRID} strokeDasharray="3 3" vertical={false} />
        <XAxis dataKey="month" tick={AXIS} axisLine={false} tickLine={false} />
        <YAxis tick={AXIS} axisLine={false} tickLine={false} tickFormatter={(v) => formatMoney(Number(v), { compact: true, withSymbol: false })} width={48} />
        <Tooltip contentStyle={tooltipStyle} cursor={{ fill: '#f1f5f9' }} formatter={(v: number) => formatMoney(v)} />
        <Bar dataKey="amount" fill={color} radius={[6, 6, 0, 0]} maxBarSize={40} />
      </BarChart>
    </ResponsiveContainer>
  )
}

const DONUT_COLORS = ['#115e59', '#2563eb', '#16a34a', '#d97706', '#dc2626', '#7c3aed', '#0d9488', '#64748b']

export function DonutChart({
  data,
  dataKey = 'count',
  nameKey = 'status',
}: {
  data: Record<string, number | string>[]
  dataKey?: string
  nameKey?: string
}) {
  return (
    <ResponsiveContainer width="100%" height={240}>
      <PieChart>
        <Pie data={data} dataKey={dataKey} nameKey={nameKey} innerRadius={58} outerRadius={92} paddingAngle={2} stroke="none">
          {data.map((_, i) => (
            <Cell key={i} fill={DONUT_COLORS[i % DONUT_COLORS.length]} />
          ))}
        </Pie>
        <Tooltip contentStyle={tooltipStyle} />
      </PieChart>
    </ResponsiveContainer>
  )
}

export { DONUT_COLORS }
