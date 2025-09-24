import React from 'react'
import { Check } from 'lucide-react'
import { Button } from './ui/Button'
import { Link } from '@inertiajs/react'
const plans = [
  {
    name: 'Basic',
    price: '$0',
    period: '/month',
    features: ['Up to 5 summaries/month', 'Basic AI insights', 'Email support'],
    cta: 'Get Started',
    link: '/register',
    popular: false,
  },
  {
    name: 'Pro',
    price: '$29',
    period: '/month',
    features: [
      'Unlimited summaries',
      'Advanced AI insights',
      'Priority support',
      'Custom branding',
    ],
    cta: 'Get Started',
    link: '/register',
    popular: true,
  },
  {
    name: 'Enterprise',
    price: 'Custom',
    period: '',
    features: [
      'All Pro features',
      'Multi-tenant isolation',
      'Dedicated support',
      'White-label options',
      'API access',
    ],
    cta: 'Contact Sales',
    link: '/register',
    popular: false,
  },
]
export const Pricing = () => {
  return (
    <section id="pricing" className="py-24 bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center">
          <h2 className="text-3xl font-extrabold text-gray-900 sm:text-4xl">
            Simple Pricing Plans
          </h2>
          <p className="mt-4 max-w-2xl mx-auto text-xl text-gray-500">
            Choose a plan that fits your needs, with full access to AI
            summarization and multi-tenant features.
          </p>
        </div>
        <div className="mt-16 grid gap-8 lg:grid-cols-3">
          {plans.map((plan, index) => (
            <div
              key={index}
              className={`relative flex flex-col rounded-2xl ${plan.popular ? 'border-2 border-slate-700 shadow-xl' : 'border border-gray-200 shadow'} bg-white p-8`}
            >
              {plan.popular && (
                <div className="absolute top-0 right-6 -translate-y-1/2 bg-slate-800 text-white px-4 py-1 rounded-full text-sm font-medium">
                  Most Popular
                </div>
              )}
              <h3 className="text-xl font-semibold text-gray-900">
                {plan.name}
              </h3>
              <div className="mt-4 flex items-baseline">
                <span className="text-4xl font-extrabold text-gray-900">
                  {plan.price}
                </span>
                <span className="ml-1 text-xl font-medium text-gray-500">
                  {plan.period}
                </span>
              </div>
              <ul className="mt-8 space-y-4 flex-grow">
                {plan.features.map((feature, featureIndex) => (
                  <li key={featureIndex} className="flex items-start">
                    <div className="flex-shrink-0">
                      <Check className="h-5 w-5 text-slate-700" />
                    </div>
                    <p className="ml-3 text-base text-gray-500">{feature}</p>
                  </li>
                ))}
              </ul>
              <div className="mt-8">
                <Link href={plan.link}>
                  <Button
                    variant={plan.popular ? 'primary' : 'outline'}
                    className="w-full"
                  >
                    {plan.cta}
                  </Button>
                </Link>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
