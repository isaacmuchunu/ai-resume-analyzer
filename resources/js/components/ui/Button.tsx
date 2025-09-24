import React from 'react'
interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'outline' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  children: React.ReactNode
  className?: string
  asChild?: boolean
}
export const Button = ({
  variant = 'primary',
  size = 'md',
  children,
  className = '',
  asChild = false,
  ...props
}: ButtonProps) => {
  const baseStyles =
    'inline-flex items-center justify-center font-medium transition-all duration-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 group'
  const variantStyles = {
    primary: 'bg-slate-800 text-white hover:bg-slate-900 shadow-sm',
    secondary:
      'bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-800',
    outline:
      'border border-gray-300 bg-transparent hover:bg-gray-50 text-gray-700',
    ghost: 'bg-transparent hover:bg-gray-100 text-gray-700 hover:text-gray-900',
  }
  const sizeStyles = {
    sm: 'text-sm px-3 py-1.5',
    md: 'text-sm px-4 py-2.5',
    lg: 'text-base px-6 py-3.5 font-semibold',
  }
  const classes = `${baseStyles} ${variantStyles[variant]} ${sizeStyles[size]} ${className}`
  if (asChild) {
    return <div className={classes}>{children}</div>
  }
  return (
    <button className={classes} {...props}>
      {children}
    </button>
  )
}
