import React from 'react'
import { Header } from '../components/Header'
import { Hero } from '../components/Hero'
import { Features } from '../components/Features'
import { HowItWorks } from '../components/HowItWorks'
import { Stats } from '../components/Stats'
import { Testimonials } from '../components/Testimonials'
import { Pricing } from '../components/Pricing'
import { FAQ } from '../components/FAQ'
import { CTA } from '../components/CTA'
import { Footer } from '../components/Footer'

const Welcome = ()=> {
  return (
    <div className="min-h-screen bg-white font-sans">
      <Header />
      <main>
        <Hero />
        <HowItWorks />
        <Features />
        <Stats />
        <Testimonials />
        <Pricing />
        <FAQ />
        <CTA />
      </main>
      <Footer />
    </div>
  )
}
export default Welcome;